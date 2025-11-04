<?php
/**
 * EduBot Admin Dashboard Tests
 * 
 * Tests for dashboard data retrieval and calculations
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_EduBot_Admin_Dashboard extends EduBot_Test_Case {
    
    /**
     * Dashboard instance
     * 
     * @var EduBot_Admin_Dashboard
     */
    private $dashboard;
    
    /**
     * Setup
     */
    public function setUp(): void {
        parent::setUp();
        
        if (class_exists('EduBot_Admin_Dashboard')) {
            $this->dashboard = new EduBot_Admin_Dashboard($this->logger);
        }
    }
    
    /**
     * Test get KPIs
     */
    public function test_get_kpis() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        $kpis = $this->dashboard->get_kpis('month');
        
        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_conversions', $kpis);
        $this->assertArrayHasKey('total_clicks', $kpis);
    }
    
    /**
     * Test get KPI summary
     */
    public function test_get_kpi_summary() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        $summary = $this->dashboard->get_kpi_summary();
        
        $this->assertIsArray($summary);
        $this->assertIsInt($summary['total_conversions'] ?? 0);
    }
    
    /**
     * Test get enquiries by source
     */
    public function test_get_enquiries_by_source() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        $sources = $this->dashboard->get_enquiries_by_source('month');
        
        $this->assertIsArray($sources);
    }
    
    /**
     * Test get enquiries by campaign
     */
    public function test_get_enquiries_by_campaign() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        $campaigns = $this->dashboard->get_enquiries_by_campaign('month');
        
        $this->assertIsArray($campaigns);
    }
    
    /**
     * Test get enquiry trends
     */
    public function test_get_enquiry_trends() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        $trends = $this->dashboard->get_enquiry_trends('month');
        
        $this->assertIsArray($trends);
    }
    
    /**
     * Test get device breakdown
     */
    public function test_get_device_breakdown() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        $devices = $this->dashboard->get_device_breakdown('month');
        
        $this->assertIsArray($devices);
    }
    
    /**
     * Test period parameter validation
     */
    public function test_period_validation() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        // Test valid periods
        $valid_periods = ['week', 'month', 'quarter', 'year'];
        
        foreach ($valid_periods as $period) {
            $kpis = $this->dashboard->get_kpis($period);
            $this->assertIsArray($kpis, "Failed for period: $period");
        }
    }
    
    /**
     * Test caching
     */
    public function test_caching() {
        if (!$this->dashboard) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        // Get data twice and verify consistency
        $result1 = $this->dashboard->get_kpis('month');
        $result2 = $this->dashboard->get_kpis('month');
        
        $this->assertEquals($result1, $result2);
    }
}
