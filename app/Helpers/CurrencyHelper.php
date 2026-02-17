<?php

if (!function_exists('formatTsh')) {
    /**
     * Format amount in Tanzanian Shillings
     *
     * @param float $amount
     * @return string
     */
    function formatTsh($amount): string
    {
        return 'TSh ' . number_format($amount, 0, '.', ',');
    }
}













