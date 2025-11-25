<?php

/*
$level['name']
$level['permissions']
$level['duration']
*/
function xenice_member_get_level($level_id) {
    $levels = get_option('xenice_member_levels', array());

    foreach ($levels as $level) {
        if ($level['id'] == $level_id) {
            return $level;
        }
    }

    return null;
}

function xenice_member_get_duration_value($level_id){
    $level = xenice_member_get_level($level_id);
    if(isset($level['duration'])){
        $key = $level['duration'];
        $values = apply_filters('xenice_member_duration_values', []);
        if(isset($values[$key])){
            return $values[$key];
        }
    }
    return 0;
}

/**
 * Check if a user has a specific permission
 *
 * @param string $permission Permission key, e.g. 'download_free'
 * @param int|null $user_id User ID (optional, defaults to current user)
 * @return bool
 */
function xenice_member_can($permission, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) return false;

    // Get user's membership level ID
    $level_id = get_user_meta($user_id, 'xm_level', true);
    if (!$level_id) return false;

    // Get all membership levels
    $levels = get_option('xenice_member_levels', array());
    
    // Find user's level
    foreach ($levels as $level) {
        if ($level['id'] == $level_id) {
            $permissions = $level['permissions'] ?? [];
            return in_array($permission, $permissions, true);
        }
    }

    return false;
}
