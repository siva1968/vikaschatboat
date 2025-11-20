<?php
/**
 * Admin Dashboard Widget Template
 * 
 * Displays the marketing analytics dashboard with charts, statistics,
 * and performance metrics.
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 * @subpackage Admin/Templates
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Initialize dashboard
$dashboard = new EduBot_Admin_Dashboard();

// Get current period (default: month)
$period = isset($_GET['dashboard_period']) ? sanitize_text_field($_GET['dashboard_period']) : 'month';
$valid_periods = ['today', 'week', 'month', 'year'];
$period = in_array($period, $valid_periods, true) ? $period : 'month';

// Get dashboard data
$kpis = $dashboard->get_kpis($period);
$comparison = $dashboard->get_enquiries_comparison($period);
$sources = $dashboard->get_enquiries_by_source($period);
$campaigns = $dashboard->get_enquiries_by_campaign($period);
$trends = $dashboard->get_enquiry_trends($period);
$devices = $dashboard->get_device_breakdown($period);
$top_sources = $dashboard->get_top_performing_sources($period);

// Prepare data for charts
$sources_labels = wp_json_encode(wp_list_pluck($sources, 'source'));
$sources_data = wp_json_encode(wp_list_pluck($sources, 'count'));
$sources_colors = wp_json_encode(wp_list_pluck($sources, 'color'));

$campaigns_labels = wp_json_encode(array_map(function($c) {
    return trim($c['campaign'] ?? 'Unknown', '"');
}, $campaigns));
$campaigns_data = wp_json_encode(wp_list_pluck($campaigns, 'enquiries'));

$trends_labels = wp_json_encode(wp_list_pluck($trends, 'date'));
$trends_data = wp_json_encode(wp_list_pluck($trends, 'count'));

$devices_labels = wp_json_encode(wp_list_pluck($devices, 'device_type'));
$devices_data = wp_json_encode(wp_list_pluck($devices, 'count'));
$devices_colors = wp_json_encode(wp_list_pluck($devices, 'color'));
?>

<style>
    .edubot-dashboard {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
    }
    
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .dashboard-title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
    }
    
    .period-selector {
        display: flex;
        gap: 10px;
    }
    
    .period-btn {
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
    }
    
    .period-btn:hover {
        border-color: #007cba;
        color: #007cba;
    }
    
    .period-btn.active {
        background: #007cba;
        color: white;
        border-color: #007cba;
    }
    
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .kpi-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid #007cba;
    }
    
    .kpi-card.compare {
        border-left-color: #28a745;
    }
    
    .kpi-card.compare.negative {
        border-left-color: #dc3545;
    }
    
    .kpi-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }
    
    .kpi-change {
        font-size: 14px;
        color: #666;
    }
    
    .kpi-change.increase {
        color: #28a745;
    }
    
    .kpi-change.decrease {
        color: #dc3545;
    }
    
    .chart-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .chart-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    .table-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 25px;
        overflow-x: auto;
    }
    
    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    
    .dashboard-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #dee2e6;
    }
    
    .dashboard-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .dashboard-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .progress-bar {
        background: #e9ecef;
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: #007cba;
        transition: width 0.3s ease;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-primary {
        background: #007cba;
        color: white;
    }
    
    .badge-success {
        background: #28a745;
        color: white;
    }
    
    .badge-warning {
        background: #ffc107;
        color: #333;
    }
    
    .badge-danger {
        background: #dc3545;
        color: white;
    }
    
    .metric-value {
        font-weight: 700;
        color: #007cba;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .kpi-row {
            grid-template-columns: 1fr;
        }
        
        .chart-row {
            grid-template-columns: 1fr;
        }
        
        .chart-container {
            height: 250px;
        }
    }
</style>

<div class="edubot-dashboard">
    
    <!-- Header -->
    <div class="dashboard-header">
        <h2 class="dashboard-title">📊 Marketing Analytics Dashboard</h2>
        <div class="period-selector">
            <a href="?dashboard_period=today" class="period-btn <?php echo $period === 'today' ? 'active' : ''; ?>">Today</a>
            <a href="?dashboard_period=week" class="period-btn <?php echo $period === 'week' ? 'active' : ''; ?>">Week</a>
            <a href="?dashboard_period=month" class="period-btn <?php echo $period === 'month' ? 'active' : ''; ?>">Month</a>
            <a href="?dashboard_period=year" class="period-btn <?php echo $period === 'year' ? 'active' : ''; ?>">Year</a>
        </div>
    </div>
    
    <!-- KPI Cards -->
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-label">Total Enquiries</div>
            <div class="kpi-value"><?php echo number_format($kpis['total_enquiries']); ?></div>
            <div class="kpi-label" style="margin-top: 10px;">This <?php echo ucfirst($period); ?></div>
        </div>
        
        <div class="kpi-card compare <?php echo $comparison['is_increase'] ? '' : 'negative'; ?>">
            <div class="kpi-label">Period Comparison</div>
            <div class="kpi-value"><?php echo $comparison['current']; ?></div>
            <div class="kpi-change <?php echo $comparison['is_increase'] ? 'increase' : 'decrease'; ?>">
                <?php echo $comparison['change_text']; ?> <?php echo abs($comparison['change_percent']); ?>% vs previous
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-label">Unique Sources</div>
            <div class="kpi-value"><?php echo $kpis['unique_sources']; ?></div>
            <div class="kpi-label" style="margin-top: 10px;">Marketing channels</div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-label">Average Per Day</div>
            <div class="kpi-value"><?php echo $kpis['avg_per_day']; ?></div>
            <div class="kpi-label" style="margin-top: 10px;">Enquiries/day</div>
        </div>
    </div>
    
    <!-- Charts Row 1 -->
    <div class="chart-row">
        
        <!-- Enquiries by Source -->
        <div class="chart-card">
            <h3 class="chart-title">📈 Enquiries by Source</h3>
            <div class="chart-container">
                <canvas id="sourceChart"></canvas>
            </div>
        </div>
        
        <!-- Conversion Trends -->
        <div class="chart-card">
            <h3 class="chart-title">📊 Conversion Trends</h3>
            <div class="chart-container">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>
        
        <!-- Device Breakdown -->
        <div class="chart-card">
            <h3 class="chart-title">📱 Device Breakdown</h3>
            <div class="chart-container">
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Campaigns Table -->
    <div class="table-card">
        <h3 class="chart-title">🎯 Top Performing Campaigns</h3>
        <?php if (!empty($campaigns)): ?>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Source</th>
                    <th>Enquiries</th>
                    <th>% of Total</th>
                    <th>Est. Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($campaigns, 0, 10) as $campaign): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html(trim($campaign['campaign'] ?? 'Unknown', '"')); ?></strong>
                    </td>
                    <td>
                        <span class="badge badge-primary">
                            <?php echo esc_html(trim($campaign['source'] ?? 'Unknown', '"')); ?>
                        </span>
                    </td>
                    <td class="metric-value"><?php echo $campaign['enquiries']; ?></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $campaign['percentage']; ?>%"></div>
                        </div>
                        <?php echo $campaign['percentage']; ?>%
                    </td>
                    <td>$<?php echo number_format($campaign['estimated_spend'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>No campaign data available yet.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Top Sources Table -->
    <div class="table-card">
        <h3 class="chart-title">⭐ Top Performing Sources</h3>
        <?php if (!empty($top_sources)): ?>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Total Enquiries</th>
                    <th>Unique Students</th>
                    <th>Conversion Rate</th>
                    <th>Enquiries/Day</th>
                    <th>Active Period</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_sources as $source): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html(trim($source['source'] ?? 'Unknown', '"')); ?></strong>
                    </td>
                    <td class="metric-value"><?php echo $source['total_enquiries']; ?></td>
                    <td><?php echo $source['unique_students']; ?></td>
                    <td>
                        <span class="badge badge-success">
                            <?php echo $source['conversion_rate']; ?>%
                        </span>
                    </td>
                    <td><?php echo $source['enquiries_per_day']; ?></td>
                    <td><?php echo $source['days_active']; ?> days</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>No source data available yet.</p>
        </div>
        <?php endif; ?>
    </div>
    
</div>

<script>
    // Chart.js configuration
    const chartConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };
    
    // Source Pie Chart
    const sourceCtx = document.getElementById('sourceChart');
    if (sourceCtx) {
        new Chart(sourceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $sources_labels; ?>,
                datasets: [{
                    data: <?php echo $sources_data; ?>,
                    backgroundColor: <?php echo $sources_colors; ?>,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: chartConfig
        });
    }
    
    // Trends Line Chart
    const trendsCtx = document.getElementById('trendsChart');
    if (trendsCtx) {
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo $trends_labels; ?>,
                datasets: [{
                    label: 'Enquiries',
                    data: <?php echo $trends_data; ?>,
                    borderColor: '#007cba',
                    backgroundColor: 'rgba(0, 124, 186, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#007cba',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return Math.floor(value);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Device Bar Chart
    const deviceCtx = document.getElementById('deviceChart');
    if (deviceCtx) {
        new Chart(deviceCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $devices_labels; ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo $devices_data; ?>,
                    backgroundColor: <?php echo $devices_colors; ?>,
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                ...chartConfig,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>

<?php
// Check if Chart.js is loaded, if not load it
if (!wp_script_is('chart-js')) {
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
        [],
        '3.9.1',
        true
    );
}
?>
