<?php

/**
 * Enhanced Analytics Admin Page for Visitor Tracking
 */

// Ensure this file is called from WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Get analytics data
$visitor_analytics = new EduBot_Visitor_Analytics();
$date_range = isset($_GET['range']) ? intval($_GET['range']) : 30;
$analytics_data = $visitor_analytics->get_visitor_analytics($date_range);

// Get database manager for existing analytics
$db_manager = new EduBot_Database_Manager();
$traditional_analytics = $db_manager->get_analytics_data($date_range);
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-chart-line"></span>
        EduBot Analytics Dashboard
    </h1>

    <!-- Date Range Selector -->
    <div class="edubot-analytics-header" style="margin-bottom: 20px;">
        <form method="get" style="display: inline-block;">
            <input type="hidden" name="page" value="edubot-analytics">
            <label for="range">Date Range:</label>
            <select name="range" id="range" onchange="this.form.submit()">
                <option value="7" <?php selected($date_range, 7); ?>>Last 7 days</option>
                <option value="30" <?php selected($date_range, 30); ?>>Last 30 days</option>
                <option value="90" <?php selected($date_range, 90); ?>>Last 90 days</option>
            </select>
        </form>
    </div>

    <!-- Visitor Overview Cards -->
    <div class="edubot-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <!-- Total Visitors -->
        <div class="edubot-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #4facfe;">
            <h3 style="margin: 0 0 10px 0; color: #333;">Total Visitors</h3>
            <div style="font-size: 32px; font-weight: bold; color: #4facfe;"><?php echo number_format($analytics_data['total_visitors']); ?></div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Unique visitors in last <?php echo $date_range; ?> days</p>
        </div>

        <!-- New vs Returning -->
        <div class="edubot-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #28a745;">
            <h3 style="margin: 0 0 10px 0; color: #333;">Visitor Types</h3>
            <?php 
            $new_visitors = 0;
            $returning_visitors = 0;
            foreach ($analytics_data['visitor_types'] as $type) {
                if ($type['visitor_type'] === 'new') {
                    $new_visitors = $type['count'];
                } else {
                    $returning_visitors = $type['count'];
                }
            }
            ?>
            <div style="font-size: 18px; margin-bottom: 5px;">
                <span style="color: #28a745; font-weight: bold;"><?php echo number_format($new_visitors); ?></span> New |
                <span style="color: #007cba; font-weight: bold;"><?php echo number_format($returning_visitors); ?></span> Returning
            </div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Return rate: <?php echo $analytics_data['total_visitors'] > 0 ? round(($returning_visitors / $analytics_data['total_visitors']) * 100, 1) : 0; ?>%</p>
        </div>

        <!-- Conversation Rate -->
        <div class="edubot-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ffc107;">
            <h3 style="margin: 0 0 10px 0; color: #333;">Engagement Rate</h3>
            <div style="font-size: 32px; font-weight: bold; color: #ffc107;"><?php echo $analytics_data['conversation_rate']; ?>%</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Visitors who started conversations</p>
        </div>

        <!-- Application Rate -->
        <div class="edubot-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #dc3545;">
            <h3 style="margin: 0 0 10px 0; color: #333;">Conversion Rate</h3>
            <div style="font-size: 32px; font-weight: bold; color: #dc3545;"><?php echo $analytics_data['application_rate']; ?>%</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Visitors who submitted applications</p>
        </div>
    </div>

    <!-- Main Analytics Content -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <!-- Conversion Funnel -->
        <div class="edubot-analytics-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0;">Conversion Funnel</h2>
            
            <?php 
            $funnel = $analytics_data['conversion_funnel'];
            $max_value = max($funnel['visitors'], 1); // Prevent division by zero
            ?>
            
            <div class="funnel-step" style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: bold;">Website Visitors</span>
                    <span style="font-size: 18px; font-weight: bold;"><?php echo number_format($funnel['visitors']); ?></span>
                </div>
                <div style="background: #e9ecef; height: 8px; border-radius: 4px; margin-top: 5px;">
                    <div style="background: #4facfe; height: 100%; width: 100%; border-radius: 4px;"></div>
                </div>
                <small style="color: #666;">100% of total traffic</small>
            </div>

            <div class="funnel-step" style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: bold;">Started Conversations</span>
                    <span style="font-size: 18px; font-weight: bold;"><?php echo number_format($funnel['conversation_starts']); ?></span>
                </div>
                <div style="background: #e9ecef; height: 8px; border-radius: 4px; margin-top: 5px;">
                    <div style="background: #28a745; height: 100%; width: <?php echo ($funnel['conversation_starts'] / $max_value) * 100; ?>%; border-radius: 4px;"></div>
                </div>
                <small style="color: #666;"><?php echo $analytics_data['conversation_rate']; ?>% conversion rate</small>
            </div>

            <div class="funnel-step" style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: bold;">Submitted Applications</span>
                    <span style="font-size: 18px; font-weight: bold;"><?php echo number_format($funnel['applications']); ?></span>
                </div>
                <div style="background: #e9ecef; height: 8px; border-radius: 4px; margin-top: 5px;">
                    <div style="background: #dc3545; height: 100%; width: <?php echo ($funnel['applications'] / $max_value) * 100; ?>%; border-radius: 4px;"></div>
                </div>
                <small style="color: #666;"><?php echo $analytics_data['application_rate']; ?>% conversion rate</small>
            </div>
        </div>

        <!-- Traffic Sources -->
        <div class="edubot-analytics-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0;">Top Traffic Sources</h2>
            
            <?php if (!empty($analytics_data['traffic_sources'])): ?>
                <div class="traffic-sources">
                    <?php foreach ($analytics_data['traffic_sources'] as $source): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <div>
                                <strong><?php echo esc_html($source['source'] ?: 'Direct'); ?></strong>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-size: 16px; font-weight: bold;"><?php echo number_format($source['count']); ?></span>
                                <br>
                                <small style="color: #666;">visitors</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No traffic source data available for this period.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Traditional Analytics Integration -->
    <div class="edubot-analytics-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-top: 0;">Application Analytics</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #007cba;"><?php echo isset($traditional_analytics['total_applications']) ? number_format($traditional_analytics['total_applications']) : '0'; ?></div>
                <div style="color: #666; font-size: 14px;">Total Applications</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo isset($traditional_analytics['completed_applications']) ? number_format($traditional_analytics['completed_applications']) : '0'; ?></div>
                <div style="color: #666; font-size: 14px;">Completed Applications</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #ffc107;"><?php echo isset($traditional_analytics['pending_applications']) ? number_format($traditional_analytics['pending_applications']) : '0'; ?></div>
                <div style="color: #666; font-size: 14px;">Pending Applications</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                <div style="font-size: 24px; font-weight: bold; color: #dc3545;"><?php echo isset($traditional_analytics['completion_rate']) ? $traditional_analytics['completion_rate'] : '0'; ?>%</div>
                <div style="color: #666; font-size: 14px;">Completion Rate</div>
            </div>
        </div>
    </div>

    <!-- Visitor Analytics Settings -->
    <div class="edubot-analytics-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0;">Analytics Configuration</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <h3>Tracking Features</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #28a745;"></span> Visitor identification and return tracking</li>
                    <li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #28a745;"></span> Marketing parameter capture (UTM codes)</li>
                    <li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #28a745;"></span> Device and browser detection</li>
                    <li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #28a745;"></span> Page view and interaction tracking</li>
                    <li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #28a745;"></span> Conversion funnel analysis</li>
                    <li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #28a745;"></span> 30-day data retention</li>
                </ul>
            </div>
            
            <div>
                <h3>Data Management</h3>
                <p style="color: #666; margin-bottom: 15px;">Analytics data is automatically cleaned up after 30 days to maintain optimal performance and comply with privacy requirements.</p>
                
                <button type="button" class="button button-secondary" onclick="refreshAnalytics()" style="margin-right: 10px;">
                    <span class="dashicons dashicons-update"></span> Refresh Data
                </button>
                
                <button type="button" class="button button-secondary" onclick="exportAnalytics()">
                    <span class="dashicons dashicons-download"></span> Export Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function refreshAnalytics() {
    location.reload();
}

function exportAnalytics() {
    // Create export functionality
    const range = <?php echo $date_range; ?>;
    const data = <?php echo json_encode($analytics_data); ?>;
    
    // Simple CSV export
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Metric,Value\n";
    csvContent += "Total Visitors," + data.total_visitors + "\n";
    csvContent += "Conversation Rate," + data.conversation_rate + "%\n";
    csvContent += "Application Rate," + data.application_rate + "%\n";
    csvContent += "Conversations Started," + data.conversion_funnel.conversation_starts + "\n";
    csvContent += "Applications Submitted," + data.conversion_funnel.applications + "\n";
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "edubot_analytics_" + range + "days.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Auto-refresh every 5 minutes
setInterval(function() {
    const refreshIndicator = document.createElement('div');
    refreshIndicator.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px 20px; border-radius: 4px; z-index: 9999;';
    refreshIndicator.textContent = 'Refreshing analytics data...';
    document.body.appendChild(refreshIndicator);
    
    setTimeout(function() {
        location.reload();
    }, 2000);
}, 300000); // 5 minutes
</script>

<style>
.edubot-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}

.traffic-sources div:last-child {
    border-bottom: none !important;
}

@media (max-width: 768px) {
    .edubot-stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
