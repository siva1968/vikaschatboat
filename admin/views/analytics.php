<?php
/**
 * Analytics View
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="edubot-analytics">
        <?php if (isset($analytics_data) && !empty($analytics_data)): ?>
            <div class="edubot-row">
                <div class="edubot-col">
                    <div class="edubot-card">
                        <h2>Key Metrics</h2>
                        <div class="metrics-grid">
                            <div class="metric-item">
                                <h3><?php echo esc_html($analytics_data['total_conversations'] ?? 0); ?></h3>
                                <p>Total Conversations</p>
                            </div>
                            <div class="metric-item">
                                <h3><?php echo esc_html($analytics_data['total_applications'] ?? 0); ?></h3>
                                <p>Applications Received</p>
                            </div>
                            <div class="metric-item">
                                <h3><?php echo esc_html(round($analytics_data['conversion_rate'] ?? 0, 1)); ?>%</h3>
                                <p>Conversion Rate</p>
                            </div>
                            <div class="metric-item">
                                <h3><?php echo esc_html(round($analytics_data['avg_completion_time'] ?? 0, 1)); ?> min</h3>
                                <p>Avg. Completion Time</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="edubot-row">
                <div class="edubot-col-6">
                    <div class="edubot-card">
                        <h2>Popular Questions</h2>
                        <?php if (isset($analytics_data['popular_questions']) && !empty($analytics_data['popular_questions'])): ?>
                            <ul class="popular-questions">
                                <?php foreach ($analytics_data['popular_questions'] as $question): ?>
                                    <li>
                                        <span class="question"><?php echo esc_html($question['question']); ?></span>
                                        <span class="count"><?php echo esc_html($question['count']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No popular questions data available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="edubot-col-6">
                    <div class="edubot-card">
                        <h2>Grade Distribution</h2>
                        <?php if (isset($analytics_data['grade_distribution']) && !empty($analytics_data['grade_distribution'])): ?>
                            <div class="grade-chart">
                                <?php foreach ($analytics_data['grade_distribution'] as $grade => $count): ?>
                                    <div class="grade-bar">
                                        <span class="grade-label"><?php echo esc_html($grade); ?></span>
                                        <div class="bar-container">
                                            <div class="bar" style="width: <?php echo esc_attr(($count / max($analytics_data['grade_distribution'])) * 100); ?>%"></div>
                                        </div>
                                        <span class="grade-count"><?php echo esc_html($count); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No grade distribution data available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="edubot-card">
                <h2>Analytics Dashboard</h2>
                <p>No analytics data available yet. Once users start interacting with your chatbot, you'll see detailed statistics and insights here.</p>
                
                <div class="getting-started">
                    <h3>Getting Started</h3>
                    <ol>
                        <li>Configure your <a href="<?php echo admin_url('admin.php?page=edubot-school-settings'); ?>">school settings</a></li>
                        <li>Set up <a href="<?php echo admin_url('admin.php?page=edubot-api-integrations'); ?>">API integrations</a></li>
                        <li>Customize your <a href="<?php echo admin_url('admin.php?page=edubot-academic-config'); ?>">academic configuration</a></li>
                        <li>Add the chatbot to your website using the shortcode: <code>[edubot_chatbot]</code></li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.edubot-analytics {
    margin-top: 20px;
}
.edubot-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.edubot-col {
    flex: 1;
}
.edubot-col-6 {
    flex: 0 0 48%;
}
.edubot-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    border-radius: 4px;
}
.edubot-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}
.metric-item {
    text-align: center;
}
.metric-item h3 {
    font-size: 2.5em;
    margin: 0;
    color: #4facfe;
}
.metric-item p {
    margin: 5px 0 0 0;
    color: #666;
}
.popular-questions {
    list-style: none;
    padding: 0;
}
.popular-questions li {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}
.popular-questions .count {
    background: #4facfe;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}
.grade-chart {
    space-y: 10px;
}
.grade-bar {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}
.grade-label {
    width: 60px;
    font-weight: bold;
}
.bar-container {
    flex: 1;
    height: 20px;
    background: #f0f0f0;
    margin: 0 10px;
    border-radius: 10px;
    overflow: hidden;
}
.bar {
    height: 100%;
    background: linear-gradient(90deg, #4facfe, #00f2fe);
    border-radius: 10px;
}
.grade-count {
    width: 30px;
    text-align: right;
    font-weight: bold;
}
.getting-started {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    margin-top: 20px;
}
.getting-started h3 {
    margin-top: 0;
}
</style>
