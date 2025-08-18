<?php
/**
 * Debug page for teacher quiz functionality
 */

define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Allow any logged-in user to access this page for debugging
if (!is_user_logged_in()) {
    wp_die('Please log in to view this page');
}

// Get current user
$user_id = get_current_user_id();

// Get all LearnDash groups for this user
$learndash_groups = learndash_get_users_group_ids($user_id);

// Get user data
$user = get_userdata($user_id);
$user_roles = $user ? $user->roles : [];

// Get all user groups with details
$group_details = [];
if (!empty($learndash_groups)) {
    foreach ($learndash_groups as $group_id) {
        $group = get_post($group_id);
        if ($group) {
            $leaders = learndash_get_groups_administrator_ids($group_id);
            $group_details[] = [
                'ID' => $group_id,
                'title' => $group->post_title,
                'author_id' => $group->post_author,
                'author_name' => get_the_author_meta('display_name', $group->post_author),
                'leaders' => $leaders,
                'leader_names' => array_map(function($leader_id) {
                    return get_the_author_meta('display_name', $leader_id);
                }, $leaders)
            ];
        }
    }
}

// Try to get teacher quiz data
$quiz_data = [];
if (function_exists('get_student_teacher_latest_quiz_direct')) {
    $quiz_data = get_student_teacher_latest_quiz_direct();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Quiz Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .debug-section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <h1>Teacher Quiz Debug</h1>
    
    <div class="debug-section">
        <h2>User Information</h2>
        <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
        <p><strong>Username:</strong> <?php echo $user ? $user->user_login : 'N/A'; ?></p>
        <p><strong>Display Name:</strong> <?php echo $user ? $user->display_name : 'N/A'; ?></p>
        <p><strong>Roles:</strong> <?php echo !empty($user_roles) ? implode(', ', $user_roles) : 'None'; ?></p>
    </div>
    
    <div class="debug-section">
        <h2>LearnDash Groups</h2>
        <?php if (!empty($group_details)): ?>
            <h3>Group Membership</h3>
            <pre><?php print_r($group_details); ?></pre>
        <?php else: ?>
            <p class="warning">User is not a member of any LearnDash groups.</p>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h2>Leader Quizzes</h2>
        <?php
        if (!empty($group_details)) {
            foreach ($group_details as $group) {
                echo '<h3>Group: ' . esc_html($group['title']) . ' (ID ' . intval($group['ID']) . ')</h3>';
                if (empty($group['leaders'])) {
                    echo '<p class="warning">No leaders for this group.</p>';
                    continue;
                }
                foreach ($group['leaders'] as $leader_id) {
                    $leader = get_userdata($leader_id);
                    echo '<h4>Leader: ' . esc_html($leader ? $leader->display_name : ('ID ' . $leader_id)) . ' (ID ' . intval($leader_id) . ')</h4>';
                    $args = array(
                        'post_type'      => 'sfwd-quiz',
                        'posts_per_page' => -1,
                        'author'         => $leader_id,
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC'
                    );
                    $q = new WP_Query($args);
                    if (!$q->have_posts()) {
                        echo '<p class="error">No quizzes found for this leader.</p>';
                    } else {
                        echo '<ul>';
                        foreach ($q->posts as $quiz) {
                            echo '<li>#' . intval($quiz->ID) . ' — ' . esc_html($quiz->post_title) . ' — <a href="' . esc_url(get_permalink($quiz->ID)) . '" target="_blank">Open</a></li>';
                        }
                        echo '</ul>';
                    }
                }
            }
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>Teacher Quiz Data</h2>
        <?php if (!empty($quiz_data)): ?>
            <h3>Quiz Found</h3>
            <pre><?php print_r($quiz_data); ?></pre>
            
            <h3>Quiz Link</h3>
            <p><a href="<?php echo esc_url($quiz_data['quiz_url']); ?>"><?php echo esc_html($quiz_data['quiz_title']); ?></a></p>
            
            <h3>Button Preview</h3>
            <a href="<?php echo esc_url($quiz_data['quiz_url']); ?>" class="dashboard-button teacher-quiz-button">
                <span class="button-icon">👨‍🏫</span>
                <span class="button-text">מבחן מורה</span>
            </a>
        <?php else: ?>
            <p class="error">No quiz data found. The function returned: <?php var_export($quiz_data); ?></p>
            
            <h3>Possible Issues:</h3>
            <ul>
                <li>User is not in any LearnDash groups</li>
                <li>Groups have no leaders/teachers assigned</li>
                <li>Teacher has no quizzes published</li>
                <li>There's an error in the get_student_teacher_latest_quiz_direct() function</li>
            </ul>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h2>Function Check</h2>
        <p><strong>get_student_teacher_latest_quiz_direct() exists:</strong> 
            <?php echo function_exists('get_student_teacher_latest_quiz_direct') ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?>
        </p>
        <p><strong>learndash_get_users_group_ids() exists:</strong> 
            <?php echo function_exists('learndash_get_users_group_ids') ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?>
        </p>
    </div>
</body>
</html>
