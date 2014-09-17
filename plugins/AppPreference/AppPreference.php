<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

--------------------------------------------
------ About the AppPreference Plugin ------
--------------------------------------------

This plugin provides several social handling tools and allows users to manage their social pages.


-------------------------------
------ Methods Available ------
-------------------------------

AppPreference::createPreference($group, $preference);

$weight = AppPreference::get($uniID, $preference);
$preference = AppPreference::getGroup($uniID, $group);

AppPreference::choice($uniID, $preference1, $preference2, $choice);

$boost = AppPreference::weightBoost($winWeight, $loseWeight);

AppPreference::set($uniID, $preference, $weight);

*/

abstract class AppPreference {
	
	
/****** Create a Preference ******/
	public static function createPreference
	(
		$group			// <str> The preference group. (e.g. "Food and Drink").
	,	$preference		// <str> The preference (e.g. "Pizza", "Ice Cream").
	)					// RETURNS <bool> TRUE if exists, FALSE on failure.
	
	// AppPreference::createPreference($group, $preference);
	{
		// Check if it already exists
		if(Database::selectValue("SELECT preference FROM preference_list WHERE pref_group=? AND preference=? LIMIT 1", array($group, $preference)))
		{
			return true;
		}
		
		// Add the Preference
		return Database::query("INSERT INTO preference_list (`pref_group`, preference) VALUES (?, ?)", array($group, $preference));
	}
	
	
/****** Get the weight of a User's Preference of something ******/
	public static function get
	(
		$uniID			// <int> The UniID of the user.
	,	$preference		// <str> The preference (e.g. "Pizza", "Ice Cream").
	)					// RETURNS <int> weight of the preference, FALSE if not tracked.
	
	// $weight = AppPreference::get($uniID, $preference);
	{
		return (int) Database::selectValue("SELECT weight FROM user_preferences WHERE uni_id=? AND preference=?", array($uniID, $preference));
	}
	
	
/****** Get the weight of a User's Preferences within a chosen preference group ******/
	public static function getGroup
	(
		$uniID			// <int> The UniID of the user.
	,	$group			// <str> The preference group to retrieve.
	)					// RETURNS <int:[str:mixed]> a list of preferences (and their corresponding weight)
	
	// $preference = AppPreference::getGroup($uniID, $group);
	{
		$prefs = Database::selectMultiple("SELECT preference FROM preference_list WHERE pref_group=?", array($group));
		
		if(count($prefs) > 0)
		{
			$prefSQL = "";
			$prefArray = array($uniID);
			
			foreach($prefs as $pref)
			{
				$prefSQL .= ($prefSQL == "" ? "" : ", ") . "?";
				$prefArray[] = $pref['preference'];
			}
			
			return Database::selectMultiple("SELECT preference, weight FROM user_preferences WHERE uni_id=? AND preference IN (" . $prefSQL . ")", $prefArray);
		}
		
		return array();
	}
	
	
/****** Update weights after a User makes a choice between two preferences ******/
	public static function choice
	(
		$uniID			// <int> The UniID of the user.
	,	$preference1	// <str> The first preference to compare (e.g. "Pizza").
	,	$preference2	// <str> The second preference to compare (e.g. "Ice Cream").
	,	$choice			// <int> The choice (1 for first, 2 for second, 0 for neither, 3 for both).
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppPreference::choice($uniID, $preference1, $preference2, $choice);
	{
		// Check if these are valid preferences
		$check = Database::selectMultiple("SELECT pref_group FROM preference_list WHERE preference IN (?, ?) LIMIT 2", array($preference1, $preference2));
		
		if(count($check) < 2) { return false; }
		
		// Check the user's values
		$prefList = array();
		
		$upP1 = true;	// TRUE if we're updating preference #1, or FALSE if we have to insert it
		$upP2 = true;	// TRUE if we're updating preference #2, or FALSE if we have to insert it
		
		$prefs = Database::selectMultiple("SELECT preference, weight FROM user_preferences WHERE uni_id=? AND preference IN (?, ?)", array($uniID, $preference1, $preference2));
		
		foreach($prefs as $pref)
		{
			$prefList[$pref['preference']] = (int) $pref['weight'];
		}
		
		if(!isset($prefList[$preference1]))
		{
			$prefList[$preference1] = 0.00;
			$upP1 = false;
		}
		
		if(!isset($prefList[$preference2]))
		{
			$prefList[$preference2] = 0.00;
			$upP2 = false;
		}
		
		// Determine the change necessary
		if($choice == 1)
		{
			$boost = AppPreference::weightBoost($prefList[$preference1], $prefList[$preference2]);
			
			$prefList[$preference1] += $boost;
			$prefList[$preference2] -= $boost;
		}
		else if($choice == 2)
		{
			$boost = AppPreference::weightBoost($prefList[$preference2], $prefList[$preference1]);
			
			$prefList[$preference1] -= $boost;
			$prefList[$preference2] += $boost;
		}
		else if($choice == 0)
		{
			$prefList[$preference1] -= 0.5;
			$prefList[$preference2] -= 0.5;
		}
		else if($choice == 3)
		{
			$prefList[$preference1] += 0.5;
			$prefList[$preference2] += 0.5;
		}
		
		// Update (or insert) the preferences
		Database::startTransaction();
		$pass = false;
		
		if($upP1)
		{
			$pass = Database::query("UPDATE user_preferences SET weight=? WHERE uni_id=? AND preference=? LIMIT 1", array($prefList[$preference1], $uniID, $preference1));
		}
		else
		{
			$pass = Database::query("INSERT INTO user_preferences (uni_id, preference, weight) VALUES (?, ?, ?)", array($uniID, $preference1, $prefList[$preference1]));
		}
		
		if(!$pass) { return Database::endTransaction(false); }
		
		// Update (or insert) the preferences
		if($upP2)
		{
			$pass = Database::query("UPDATE user_preferences SET weight=? WHERE uni_id=? AND preference=? LIMIT 1", array($prefList[$preference2], $uniID, $preference2));
		}
		else
		{
			$pass = Database::query("INSERT INTO user_preferences (uni_id, preference, weight) VALUES (?, ?, ?)", array($uniID, $preference2, $prefList[$preference2]));
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Run the weight-boost algorithm ******/
	public static function weightBoost
	(
		$winWeight		// <float> the weight of the winning preference.
	,	$loseWeight		// <float> the weight of the losing preference.
	)					// RETURNS <float> the amount of boost to provide, FALSE on failure.
	
	// $boost = AppPreference::weightBoost($winWeight, $loseWeight);
	{	
		$boost = 1.00;
		
		if($winWeight > $loseWeight)
		{
			$boost *= (1 - (0.2 * ($winWeight - $loseWeight)));
			
			if($boost < 0.1) { $boost = 0.1; }
		}
		else if($winWeight < $loseWeight)
		{
			$boost *= (1 + (0.2 * ($loseWeight - $winWeight)));
		}
		
		return (float) $boost;
	}
	
	
/****** Set a User's Preference of something ******/
	public static function set
	(
		$uniID			// <int> The UniID of the user.
	,	$preference		// <str> The preference (e.g. "Pizza", "Ice Cream").
	,	$weight			// <int> The weight of interest in this particular preference. (1 = good, -1 = poor).
	)					// RETURNS <bool> TRUE if exists, FALSE on failure.
	
	// AppPreference::set($uniID, $preference, $weight);
	{
		// Check if this is a valid preference
		if(Database::selectValue("SELECT pref_group FROM preference_list WHERE preference=? LIMIT 1", array($preference)))
		{
			return false;
		}
		
		// Check if the user already has this value set
		if(Database::selectValue("SELECT weight FROM user_preferences WHERE uni_id=? AND preference=?", array($uniID, $preference)))
		{
			return Database::query("UPDATE user_preferences SET weight=? WHERE uni_id=? AND preference=?", array($weight, $uniID, $preference));
		}
		
		// Set the User's Preference level of the thing
		return Database::query("INSERT INTO user_preferences (uni_id, preference, weight) VALUES (?, ?, ?)", array($uniID, $preference, $weight));
	}
}