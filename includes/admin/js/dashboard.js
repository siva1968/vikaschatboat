/**
 * EduBot Dashboard JavaScript
 * 
 * Handles interactive dashboard features including:
 * - Chart interactivity and animations
 * - Period selection and auto-refresh
 * - Data export (CSV, PDF)
 * - Real-time updates
 * - Responsive adjustments
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 */

(function($) {
    'use strict';
    
    // Dashboard controller
    const EduBotDashboard = {
        
        // Configuration
        config: edubot_dashboard_config || {},
        
        // Chart instances
        charts: {},
        
        // Auto-refresh interval ID
        refreshInterval: null,
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.setupEventListeners();
            this.setupCharts();
            this.setupAutoRefresh();
            this.setupDataExport();
            this.setupResponsive();
        },
        
        /**
         * Setup event listeners
         */
        setupEventListeners: function() {
            const self = this;
            
            // Period buttons
            $('.period-btn').on('click', function(e) {
                e.preventDefault();
                const period = $(this).attr('href').split('dashboard_period=')[1];
                self.changePeriod(period);
            });
            
            // Export buttons
            $(document).on('click', '.export-csv-btn', function(e) {
                e.preventDefault();
                self.exportToCSV();
            });
            
            $(document).on('click', '.export-pdf-btn', function(e) {
                e.preventDefault();
                self.exportToPDF();
            });
            
            // Manual refresh button
            $(document).on('click', '.refresh-dashboard-btn', function(e) {
                e.preventDefault();
                self.refreshDashboard();
            });
            
            // Table row hover effects
            $('.dashboard-table tbody tr').on('hover', function() {
                $(this).css('cursor', 'pointer');
            });
        },
        
        /**
         * Setup chart instances
         */
        setupCharts: function() {
            const self = this;
            
            // Wait for Chart.js to load
            if (typeof Chart !== 'undefined') {
                this.initializeCharts();
            } else {
                setTimeout(() => this.setupCharts(), 100);
            }
        },
        
        /**
         * Initialize all charts
         */
        initializeCharts: function() {
            this.charts = {};
            
            // Source chart
            const sourceCanvas = document.getElementById('sourceChart');
            if (sourceCanvas) {
                this.charts.source = new Chart(sourceCanvas, this.getSourceChartConfig());
            }
            
            // Trends chart
            const trendsCanvas = document.getElementById('trendsChart');
            if (trendsCanvas) {
                this.charts.trends = new Chart(trendsCanvas, this.getTrendsChartConfig());
            }
            
            // Device chart
            const deviceCanvas = document.getElementById('deviceChart');
            if (deviceCanvas) {
                this.charts.device = new Chart(deviceCanvas, this.getDeviceChartConfig());
            }
        },
        
        /**
         * Get source chart configuration
         */
        getSourceChartConfig: function() {
            return {
                type: 'doughnut',
                data: {
                    labels: this.getChartData('sourceLabels'),
                    datasets: [{
                        data: this.getChartData('sourceData'),
                        backgroundColor: this.getChartData('sourceColors'),
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };
        },
        
        /**
         * Get trends chart configuration
         */
        getTrendsChartConfig: function() {
            return {
                type: 'line',
                data: {
                    labels: this.getChartData('trendLabels'),
                    datasets: [{
                        label: 'Enquiries',
                        data: this.getChartData('trendData'),
                        borderColor: '#007cba',
                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#007cba',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 13
                            },
                            bodyFont: {
                                size: 12
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => Math.floor(value)
                            }
                        }
                    }
                }
            };
        },
        
        /**
         * Get device chart configuration
         */
        getDeviceChartConfig: function() {
            return {
                type: 'bar',
                data: {
                    labels: this.getChartData('deviceLabels'),
                    datasets: [{
                        label: 'Count',
                        data: this.getChartData('deviceData'),
                        backgroundColor: this.getChartData('deviceColors'),
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.parsed.x} enquiries`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => Math.floor(value)
                            }
                        }
                    }
                }
            };
        },
        
        /**
         * Extract chart data from DOM
         */
        getChartData: function(type) {
            switch (type) {
                case 'sourceLabels':
                    return this.parseChartData('sourceChart', 'labels');
                case 'sourceData':
                    return this.parseChartData('sourceChart', 'data');
                case 'sourceColors':
                    return this.parseChartData('sourceChart', 'colors');
                case 'trendLabels':
                    return this.parseChartData('trendsChart', 'labels');
                case 'trendData':
                    return this.parseChartData('trendsChart', 'data');
                case 'deviceLabels':
                    return this.parseChartData('deviceChart', 'labels');
                case 'deviceData':
                    return this.parseChartData('deviceChart', 'data');
                case 'deviceColors':
                    return this.parseChartData('deviceChart', 'colors');
                default:
                    return [];
            }
        },
        
        /**
         * Parse chart data from hidden elements
         */
        parseChartData: function(chartId, dataType) {
            // In production, this would extract from the page HTML
            // For now, returning empty array (data is rendered server-side)
            return [];
        },
        
        /**
         * Change period and refresh
         */
        changePeriod: function(period) {
            const url = new URL(window.location);
            url.searchParams.set('dashboard_period', period);
            window.location = url.toString();
        },
        
        /**
         * Setup auto-refresh
         */
        setupAutoRefresh: function() {
            const self = this;
            
            // Auto-refresh every 5 minutes
            this.refreshInterval = setInterval(() => {
                if (document.hidden === false) {
                    self.refreshDashboard();
                }
            }, 300000); // 5 minutes
        },
        
        /**
         * Refresh dashboard data
         */
        refreshDashboard: function() {
            const self = this;
            
            // Show loading state
            $('.edubot-dashboard').addClass('loading');
            
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'edubot_refresh_dashboard',
                    nonce: this.config.nonce,
                    period: this.config.period
                },
                success: function(response) {
                    if (response.success) {
                        // Update dashboard data
                        self.updateDashboardData(response.data);
                        
                        // Show success notification
                        self.showNotification('Dashboard updated!', 'success');
                    } else {
                        self.showNotification('Failed to update dashboard', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Error updating dashboard', 'error');
                },
                complete: function() {
                    $('.edubot-dashboard').removeClass('loading');
                }
            });
        },
        
        /**
         * Update dashboard with new data
         */
        updateDashboardData: function(data) {
            // Update KPI cards
            if (data.kpis) {
                this.updateKPIs(data.kpis);
            }
            
            // Update charts
            if (data.charts) {
                this.updateCharts(data.charts);
            }
            
            // Update tables
            if (data.tables) {
                this.updateTables(data.tables);
            }
        },
        
        /**
         * Update KPI values
         */
        updateKPIs: function(kpis) {
            // Animate KPI updates
            $('.kpi-value').each(function(index, el) {
                const $el = $(el);
                const newValue = kpis[index];
                if (newValue !== undefined) {
                    this.animateValue($el, parseFloat($el.text().replace(/,/g, '')), newValue, 800);
                }
            }.bind(this));
        },
        
        /**
         * Animate numeric value changes
         */
        animateValue: function($element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 16); // 60fps
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    $element.text(Number(end).toLocaleString());
                    clearInterval(timer);
                } else {
                    $element.text(Number(Math.ceil(current)).toLocaleString());
                }
            }, 16);
        },
        
        /**
         * Update chart data
         */
        updateCharts: function(data) {
            // Update source chart
            if (this.charts.source && data.source) {
                this.charts.source.data = data.source;
                this.charts.source.update('active');
            }
            
            // Update trends chart
            if (this.charts.trends && data.trends) {
                this.charts.trends.data = data.trends;
                this.charts.trends.update('active');
            }
            
            // Update device chart
            if (this.charts.device && data.device) {
                this.charts.device.data = data.device;
                this.charts.device.update('active');
            }
        },
        
        /**
         * Update table data
         */
        updateTables: function(data) {
            // Update campaigns table
            if (data.campaigns) {
                this.updateTable('#campaigns-table', data.campaigns);
            }
            
            // Update sources table
            if (data.sources) {
                this.updateTable('#sources-table', data.sources);
            }
        },
        
        /**
         * Update table with new data
         */
        updateTable: function(selector, data) {
            const $table = $(selector);
            const $tbody = $table.find('tbody');
            $tbody.empty();
            
            data.forEach(row => {
                const $row = $('<tr>');
                Object.values(row).forEach(cell => {
                    $row.append(`<td>${cell}</td>`);
                });
                $tbody.append($row);
            });
        },
        
        /**
         * Setup data export functionality
         */
        setupDataExport: function() {
            this.addExportButtons();
        },
        
        /**
         * Add export buttons to dashboard
         */
        addExportButtons: function() {
            const exportBtnHTML = `
                <div class="export-buttons" style="margin-bottom: 20px;">
                    <button class="button export-csv-btn">📥 Export to CSV</button>
                    <button class="button export-pdf-btn">📄 Export to PDF</button>
                </div>
            `;
            
            $('.dashboard-header').after(exportBtnHTML);
        },
        
        /**
         * Export dashboard to CSV
         */
        exportToCSV: function() {
            const period = this.config.period;
            const timestamp = new Date().toISOString().split('T')[0];
            let csv = 'EduBot Analytics Dashboard Export\n';
            csv += `Period: ${period}\n`;
            csv += `Exported: ${new Date().toLocaleString()}\n\n`;
            
            // Export KPI data
            csv += 'KPI Summary\n';
            csv += 'Metric,Value\n';
            $('.kpi-card').each(function() {
                const label = $(this).find('.kpi-label').first().text();
                const value = $(this).find('.kpi-value').text();
                csv += `"${label}","${value}"\n`;
            });
            
            csv += '\n\nCampaigns\n';
            csv += this.tableToCSV('#campaigns-table tbody');
            
            csv += '\n\nTop Sources\n';
            csv += this.tableToCSV('#sources-table tbody');
            
            // Download
            this.downloadCSV(csv, `edubot-dashboard-${timestamp}.csv`);
        },
        
        /**
         * Convert table to CSV
         */
        tableToCSV: function(selector) {
            let csv = '';
            
            // Headers
            $(selector).closest('table').find('thead th').each(function() {
                csv += `"${$(this).text()}",`;
            });
            csv = csv.slice(0, -1) + '\n';
            
            // Rows
            $(selector).find('tr').each(function() {
                $(this).find('td').each(function() {
                    csv += `"${$(this).text().trim()}",`;
                });
                csv = csv.slice(0, -1) + '\n';
            });
            
            return csv;
        },
        
        /**
         * Download CSV file
         */
        downloadCSV: function(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        /**
         * Export dashboard to PDF
         */
        exportToPDF: function() {
            if (typeof html2pdf === 'undefined') {
                this.showNotification('PDF library not loaded', 'error');
                return;
            }
            
            const element = $('.edubot-dashboard')[0];
            const timestamp = new Date().toISOString().split('T')[0];
            
            const options = {
                margin: 10,
                filename: `edubot-dashboard-${timestamp}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };
            
            html2pdf().set(options).from(element).save();
        },
        
        /**
         * Setup responsive behavior
         */
        setupResponsive: function() {
            $(window).on('resize', () => {
                this.charts.source && this.charts.source.resize();
                this.charts.trends && this.charts.trends.resize();
                this.charts.device && this.charts.device.resize();
            });
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const bgColor = type === 'success' ? '#28a745' : 
                           type === 'error' ? '#dc3545' : '#007cba';
            
            const $notification = $(`
                <div style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${bgColor};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 4px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                    z-index: 9999;
                    animation: slideIn 0.3s ease;
                ">${message}</div>
            `);
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.fadeOut(300, () => $notification.remove());
            }, 3000);
        },
        
        /**
         * Cleanup on unload
         */
        destroy: function() {
            clearInterval(this.refreshInterval);
            $(window).off('resize');
            $('.period-btn').off('click');
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        EduBotDashboard.init();
    });
    
    // Cleanup on page unload
    $(window).on('unload', function() {
        EduBotDashboard.destroy();
    });
    
})(jQuery);
