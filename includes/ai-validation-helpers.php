<?php
/**
 * AI Validation Integration Helper
 * 
 * Provides functions to integrate AI validation as a fallback layer
 * in the chatbot's input parsing and validation pipeline.
 * 
 * @package EduBot_Pro
 * @subpackage AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Use AI to validate phone number if regex/pattern fails
 * 
 * @param string $input User input
 * @param array $parsed Previously parsed data
 * @return array AI validation result or null if AI disabled
 */
function edubot_ai_validate_phone( $input, $parsed = array() ) {
    global $edubot_ai_validator;
    
    if ( ! isset( $edubot_ai_validator ) ) {
        return null;
    }
    
    $settings = $edubot_ai_validator->get_settings();
    
    // Skip if AI not enabled or not using as fallback
    if ( ! $settings['enabled'] || ! $settings['use_as_fallback'] ) {
        return null;
    }
    
    // Only use AI if regex failed to identify a clear phone
    if ( ! empty( $parsed['phone'] ) && empty( $parsed['phone_invalid'] ) ) {
        // Regex already found valid phone
        return null;
    }
    
    // Call AI to validate
    $result = $edubot_ai_validator->validate_phone( $input );
    
    return $result;
}

/**
 * Use AI to validate grade if regex/pattern fails
 * 
 * @param string $input User input
 * @param array $parsed Previously parsed data
 * @return array AI validation result or null if AI disabled
 */
function edubot_ai_validate_grade( $input, $parsed = array() ) {
    global $edubot_ai_validator;
    
    if ( ! isset( $edubot_ai_validator ) ) {
        return null;
    }
    
    $settings = $edubot_ai_validator->get_settings();
    
    // Skip if AI not enabled or not using as fallback
    if ( ! $settings['enabled'] || ! $settings['use_as_fallback'] ) {
        return null;
    }
    
    // Only use AI if extraction returned null or empty
    if ( ! empty( $parsed['grade'] ) ) {
        // Regex already found grade
        return null;
    }
    
    // Call AI to validate
    $result = $edubot_ai_validator->validate_grade( $input );
    
    return $result;
}

/**
 * Log AI validation use for analytics
 * 
 * @param string $field Field being validated (phone, grade, etc)
 * @param string $input Original input
 * @param array $ai_result AI result
 * @param bool $used Whether the AI result was used
 */
function edubot_log_ai_usage( $field, $input, $ai_result, $used = false ) {
    error_log( sprintf(
        '[EduBot AI] Field: %s | Input: %s | Used: %s | Result: %s',
        $field,
        $input,
        $used ? 'YES' : 'NO',
        wp_json_encode( $ai_result )
    ) );
}
