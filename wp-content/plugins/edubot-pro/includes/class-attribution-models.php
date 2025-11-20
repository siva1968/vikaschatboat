<?php
/**
 * Attribution Models Class
 * 
 * Defines and implements different attribution models for multi-touch attribution.
 * Provides detailed implementations of:
 * - First-touch attribution
 * - Last-touch attribution
 * - Linear attribution
 * - Time-decay attribution
 * - U-shaped attribution
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 * @subpackage Analytics
 */

class EduBot_Attribution_Models {
    
    /**
     * Get available attribution models
     * 
     * @return array Array of model definitions
     */
    public static function get_available_models() {
        return [
            'first-touch' => [
                'name' => 'First-Touch',
                'description' => 'Credits 100% to the first marketing touchpoint',
                'best_for' => 'Awareness and brand discovery campaigns',
                'use_case' => 'When you want to understand what channels drive initial awareness'
            ],
            'last-touch' => [
                'name' => 'Last-Touch',
                'description' => 'Credits 100% to the last marketing touchpoint',
                'best_for' => 'Direct response and conversion campaigns',
                'use_case' => 'When you want to understand what channels drive final conversions'
            ],
            'linear' => [
                'name' => 'Linear',
                'description' => 'Distributes credit equally across all touchpoints',
                'best_for' => 'Balanced view of all channels',
                'use_case' => 'When you want a fair distribution without bias to first or last'
            ],
            'time-decay' => [
                'name' => 'Time-Decay',
                'description' => 'Gives more weight to touchpoints closer to conversion',
                'best_for' => 'Understanding touchpoints leading directly to conversion',
                'use_case' => 'When recent interactions are most important'
            ],
            'u-shaped' => [
                'name' => 'U-Shaped (40-20-40)',
                'description' => 'Credits 40% to first, 40% to last, 20% split among middle',
                'best_for' => 'Balanced view of awareness and conversion',
                'use_case' => 'When both initial awareness and final conversion matter equally'
            ]
        ];
    }
    
    /**
     * Get model description
     * 
     * @param string $model Model name
     * 
     * @return array Model details
     */
    public static function get_model_details($model) {
        $models = self::get_available_models();
        return isset($models[$model]) ? $models[$model] : $models['last-touch'];
    }
    
    /**
     * Calculate attribution weights using first-touch model
     * 
     * Model Logic:
     * - 100% credit to the first touchpoint
     * - 0% to all other touchpoints
     * 
     * Example:
     * - Touchpoint 1 (Facebook): 100% credit
     * - Touchpoint 2 (Google Search): 0% credit
     * - Touchpoint 3 (Direct): 0% credit
     * 
     * Use Case: Measuring awareness and initial interest
     * 
     * @param array $touchpoints Array of touchpoint objects
     * 
     * @return array Array with touchpoint IDs and their weights
     */
    public static function calculate_first_touch($touchpoints) {
        
        if (empty($touchpoints)) {
            return [];
        }
        
        $result = [];
        $count = count($touchpoints);
        
        foreach ($touchpoints as $index => $touchpoint) {
            $result[] = [
                'touchpoint_id' => $touchpoint->touchpoint_id,
                'source' => $touchpoint->source,
                'weight' => ($index === 0) ? 100.0 : 0.0,
                'position' => $index + 1,
                'total_positions' => $count,
                'reasoning' => ($index === 0) 
                    ? 'First interaction - receives all credit' 
                    : 'Not first - receives no credit'
            ];
        }
        
        return $result;
    }
    
    /**
     * Calculate attribution weights using last-touch model
     * 
     * Model Logic:
     * - 100% credit to the last/most recent touchpoint
     * - 0% to all previous touchpoints
     * 
     * Example:
     * - Touchpoint 1 (Facebook): 0% credit
     * - Touchpoint 2 (Google Search): 0% credit
     * - Touchpoint 3 (Direct): 100% credit
     * 
     * Use Case: Understanding final conversion drivers
     * 
     * @param array $touchpoints Array of touchpoint objects
     * 
     * @return array Array with touchpoint IDs and their weights
     */
    public static function calculate_last_touch($touchpoints) {
        
        if (empty($touchpoints)) {
            return [];
        }
        
        $result = [];
        $count = count($touchpoints);
        $last_index = $count - 1;
        
        foreach ($touchpoints as $index => $touchpoint) {
            $result[] = [
                'touchpoint_id' => $touchpoint->touchpoint_id,
                'source' => $touchpoint->source,
                'weight' => ($index === $last_index) ? 100.0 : 0.0,
                'position' => $index + 1,
                'total_positions' => $count,
                'reasoning' => ($index === $last_index) 
                    ? 'Last interaction - receives all credit' 
                    : 'Previous interaction - receives no credit'
            ];
        }
        
        return $result;
    }
    
    /**
     * Calculate attribution weights using linear model
     * 
     * Model Logic:
     * - Equal weight distributed across all touchpoints
     * - Each touchpoint receives 100 / number_of_touchpoints
     * 
     * Example (3 touchpoints):
     * - Touchpoint 1 (Facebook): 33.33% credit
     * - Touchpoint 2 (Google Search): 33.33% credit
     * - Touchpoint 3 (Direct): 33.33% credit
     * 
     * Use Case: Fair assessment of all channels
     * 
     * @param array $touchpoints Array of touchpoint objects
     * 
     * @return array Array with touchpoint IDs and their weights
     */
    public static function calculate_linear($touchpoints) {
        
        if (empty($touchpoints)) {
            return [];
        }
        
        $result = [];
        $count = count($touchpoints);
        $weight = 100.0 / $count;
        
        foreach ($touchpoints as $index => $touchpoint) {
            $result[] = [
                'touchpoint_id' => $touchpoint->touchpoint_id,
                'source' => $touchpoint->source,
                'weight' => round($weight, 2),
                'position' => $index + 1,
                'total_positions' => $count,
                'reasoning' => sprintf('Equal share: 100 / %d = %.2f%%', $count, $weight)
            ];
        }
        
        return $result;
    }
    
    /**
     * Calculate attribution weights using time-decay model
     * 
     * Model Logic:
     * - More weight to touchpoints closer to conversion
     * - Uses exponential decay to earlier touchpoints
     * - Formula: weight_position_i = (i / n) * 100, then normalized
     * 
     * Example (3 touchpoints):
     * - Touchpoint 1 (Facebook): 16.67% credit
     * - Touchpoint 2 (Google Search): 33.33% credit
     * - Touchpoint 3 (Direct): 50.00% credit
     * 
     * Use Case: Emphasizing final touchpoints in customer journey
     * 
     * @param array $touchpoints Array of touchpoint objects
     * @param float $decay_rate Decay rate (default: 0.5, higher = slower decay)
     * 
     * @return array Array with touchpoint IDs and their weights
     */
    public static function calculate_time_decay($touchpoints, $decay_rate = 0.5) {
        
        if (empty($touchpoints)) {
            return [];
        }
        
        $result = [];
        $count = count($touchpoints);
        $weights = [];
        
        // Calculate exponential weights
        for ($i = 0; $i < $count; $i++) {
            // Exponential function: e^(decay_rate * position)
            $position_weight = pow(M_E, $decay_rate * ($i + 1));
            $weights[$i] = $position_weight;
        }
        
        // Normalize to 100
        $sum = array_sum($weights);
        
        foreach ($touchpoints as $index => $touchpoint) {
            $normalized_weight = ($weights[$index] / $sum) * 100;
            
            $result[] = [
                'touchpoint_id' => $touchpoint->touchpoint_id,
                'source' => $touchpoint->source,
                'weight' => round($normalized_weight, 2),
                'position' => $index + 1,
                'total_positions' => $count,
                'reasoning' => sprintf('Exponential decay: Position %d/%d', $index + 1, $count)
            ];
        }
        
        return $result;
    }
    
    /**
     * Calculate attribution weights using U-shaped (40-20-40) model
     * 
     * Model Logic:
     * - 40% credit to first touchpoint (awareness)
     * - 40% credit to last touchpoint (conversion)
     * - 20% credit distributed equally among middle touchpoints
     * 
     * Example (4 touchpoints):
     * - Touchpoint 1 (Facebook): 40% credit
     * - Touchpoint 2 (Google Search): 10% credit (20% / 2 middle points)
     * - Touchpoint 3 (Email): 10% credit (20% / 2 middle points)
     * - Touchpoint 4 (Direct): 40% credit
     * 
     * Use Case: Balancing awareness and conversion importance
     * 
     * @param array $touchpoints Array of touchpoint objects
     * 
     * @return array Array with touchpoint IDs and their weights
     */
    public static function calculate_u_shaped($touchpoints) {
        
        if (empty($touchpoints)) {
            return [];
        }
        
        $result = [];
        $count = count($touchpoints);
        $last_index = $count - 1;
        
        // Calculate middle touchpoint weight
        $middle_count = max(1, $count - 2);
        $middle_weight = 20.0 / $middle_count;
        
        foreach ($touchpoints as $index => $touchpoint) {
            if ($index === 0) {
                // First touchpoint gets 40%
                $weight = 40.0;
                $reasoning = 'First interaction (awareness) - 40%';
            } elseif ($index === $last_index && $count > 1) {
                // Last touchpoint gets 40%
                $weight = 40.0;
                $reasoning = 'Last interaction (conversion) - 40%';
            } else {
                // Middle touchpoints share 20%
                $weight = $middle_weight;
                $reasoning = sprintf('Middle interaction - %d%% of middle 20%% (20%% / %d = %.2f%%)', 20, $middle_count, $middle_weight);
            }
            
            $result[] = [
                'touchpoint_id' => $touchpoint->touchpoint_id,
                'source' => $touchpoint->source,
                'weight' => round($weight, 2),
                'position' => $index + 1,
                'total_positions' => $count,
                'reasoning' => $reasoning
            ];
        }
        
        return $result;
    }
    
    /**
     * Compare multiple attribution models for same journey
     * 
     * @param array $touchpoints Array of touchpoint objects
     * 
     * @return array Comparison of all models
     */
    public static function compare_models($touchpoints) {
        
        return [
            'first-touch' => self::calculate_first_touch($touchpoints),
            'last-touch' => self::calculate_last_touch($touchpoints),
            'linear' => self::calculate_linear($touchpoints),
            'time-decay' => self::calculate_time_decay($touchpoints),
            'u-shaped' => self::calculate_u_shaped($touchpoints)
        ];
    }
    
    /**
     * Get summary of model weights by channel
     * 
     * @param array $model_results Attribution results from calculate_* method
     * 
     * @return array Summary grouped by source/channel
     */
    public static function get_summary_by_channel($model_results) {
        
        $summary = [];
        
        foreach ($model_results as $result) {
            $source = $result['source'];
            
            if (!isset($summary[$source])) {
                $summary[$source] = [
                    'source' => $source,
                    'total_weight' => 0,
                    'touchpoint_count' => 0,
                    'average_weight' => 0
                ];
            }
            
            $summary[$source]['total_weight'] += $result['weight'];
            $summary[$source]['touchpoint_count']++;
        }
        
        // Calculate averages
        foreach ($summary as &$channel) {
            $channel['average_weight'] = 
                $channel['touchpoint_count'] > 0 
                ? $channel['total_weight'] / $channel['touchpoint_count'] 
                : 0;
        }
        unset($channel);
        
        // Sort by total weight descending
        usort($summary, function($a, $b) {
            return $b['total_weight'] <=> $a['total_weight'];
        });
        
        return $summary;
    }
    
    /**
     * Generate human-readable attribution report
     * 
     * @param array $touchpoints Array of touchpoint objects
     * @param string $model Model name
     * 
     * @return string Formatted report
     */
    public static function generate_report($touchpoints, $model = 'last-touch') {
        
        $method = 'calculate_' . str_replace('-', '_', $model);
        
        if (!method_exists(__CLASS__, $method)) {
            $method = 'calculate_last_touch';
        }
        
        $model_results = self::$method($touchpoints);
        $summary = self::get_summary_by_channel($model_results);
        
        $report = sprintf(
            "=== Attribution Report (%s Model) ===\n\n",
            self::get_model_details($model)['name']
        );
        
        $report .= "Journey Overview:\n";
        $report .= sprintf("Total Touchpoints: %d\n", count($touchpoints));
        $report .= sprintf("Duration: %s to %s\n\n", 
            $touchpoints[0]->timestamp,
            end($touchpoints)->timestamp
        );
        
        $report .= "Channel Credit Distribution:\n";
        foreach ($summary as $channel) {
            $report .= sprintf(
                "- %s: %.2f%% (based on %d touchpoint(s))\n",
                $channel['source'],
                $channel['total_weight'],
                $channel['touchpoint_count']
            );
        }
        
        $report .= "\nDetailed Touchpoint Breakdown:\n";
        foreach ($model_results as $result) {
            $report .= sprintf(
                "%d. %s (%.2f%% credit) - %s\n",
                $result['position'],
                $result['source'],
                $result['weight'],
                $result['reasoning']
            );
        }
        
        return $report;
    }
}
?>
