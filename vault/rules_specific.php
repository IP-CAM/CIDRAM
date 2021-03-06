<?php
/**
 * This file is a part of the CIDRAM package.
 * Homepage: https://cidram.github.io/
 *
 * CIDRAM COPYRIGHT 2016 and beyond by Caleb Mazalevskis (Maikuolan).
 *
 * License: GNU/GPLv2
 * @see LICENSE.txt
 *
 * This file: Extended rules for some specific CIDRs (last modified: 2020.01.01).
 */

/** Prevents execution from outside of CIDRAM. */
if (!defined('CIDRAM')) {
    die('[CIDRAM] This should not be accessed directly.');
}

/** Prevents execution from outside of the CheckFactors closure. */
if (!isset($Factors[$FactorIndex])) {
    die('[CIDRAM] This should not be accessed directly.');
}

/** Safety. */
if (!isset($CIDRAM['RunParamResCache'])) {
    $CIDRAM['RunParamResCache'] = [];
}

/**
 * Define object for these rules for later recall (all parameters inherited from CheckFactors).
 *
 * @param array $Factors All CIDR factors of the IP being checked.
 * @param int $FactorIndex The index of the CIDR factor of the triggered rule.
 * @param string $LN The line information generated by CheckFactors.
 * @param string $Tag The triggered rule's section's name (if there's any).
 */
$CIDRAM['RunParamResCache']['rules_specific.php'] = function (array $Factors = [], int $FactorIndex = 0, string $LN = '', string $Tag = '') use (&$CIDRAM) {

    /** Handle PSINet prefixes here. */
    if ($Tag === 'PSINet, Inc') {

        /** Skip processing if signatures have already been triggered or if the "block_generic" directive is false. */
        if ($CIDRAM['BlockInfo']['SignatureCount'] > 0 || !$CIDRAM['Config']['signatures']['block_generic']) {
            return;
        }

        $CIDRAM['BlockInfo']['ReasonMessage'] = $CIDRAM['L10N']->getString('ReasonMessage_Generic');
        if (!empty($CIDRAM['BlockInfo']['WhyReason'])) {
            $CIDRAM['BlockInfo']['WhyReason'] .= ', ';
        }
        $CIDRAM['BlockInfo']['WhyReason'] .= $CIDRAM['L10N']->getString('Short_Generic') . $LN;
        if (!empty($CIDRAM['BlockInfo']['Signatures'])) {
            $CIDRAM['BlockInfo']['Signatures'] .= ', ';
        }
        $CIDRAM['BlockInfo']['Signatures'] .= $Factors[$FactorIndex];
        $CIDRAM['BlockInfo']['SignatureCount']++;

        /** Exit. */
        return;
    }

    /** Skip further processing if the "block_cloud" directive is false, or if no section tag has been defined. */
    if (!$CIDRAM['Config']['signatures']['block_cloud'] || !$Tag) {
        return;
    }

    /** Amazon AWS bypasses. */
    if ($Tag === 'Amazon.com, Inc') {

        /**
         * Feedspot bypass.
         * See: https://udger.com/resources/ua-list/bot-detail?bot=Feedspotbot
         */
        if (
            strpos($CIDRAM['BlockInfo']['UA'], '+https://www.feedspot.com/fs/fetcher') !== false &&
            ($Factors[31] === '54.186.248.49/32' || $Factors[31] === '54.245.252.119/32')
        ) {
            return;
        }

        /** DuckDuckGo bypass. */
        if (preg_match('~duckduck(?:go-favicons-)?bot~', $CIDRAM['BlockInfo']['UALC'])) {
            return;
        }

        /** Pinterest bypass. */
        if (strpos($CIDRAM['BlockInfo']['UALC'], 'pinterest') !== false) {
            return;
        }

        /** Embedly bypass. */
        if (strpos($CIDRAM['BlockInfo']['UALC'], 'embedly') !== false) {
            return;
        }
    }

    /** Bingbot bypasses. */
    if ($Tag === 'Azure' && preg_match('~(?:msn|bing)bot|bingpreview~', $CIDRAM['BlockInfo']['UALC'])) {
        $CIDRAM['Flag-Bypass-Bingbot-Check'] = true;
        return 2;
    }

    /** Automattic bypasses. */
    if ($Tag === 'Automattic') {

        /** Feedbot bypass. */
        if (strpos($CIDRAM['BlockInfo']['UALC'], 'wp.com feedbot/1.0 (+https://wp.com)') !== false) {
            return;
        }

        /** Jetpack bypass. */
        if (strpos($CIDRAM['BlockInfo']['UALC'], 'jetpack') !== false) {
            return;
        }
    }

    /** Disqus bypass. */
    if ($Tag === 'SoftLayer' && strpos($CIDRAM['BlockInfo']['UALC'], 'disqus') !== false) {
        $CIDRAM['Flag-Bypass-Bingbot-Check'] = true;
        return;
    }

    $CIDRAM['BlockInfo']['ReasonMessage'] = $CIDRAM['L10N']->getString('ReasonMessage_Cloud');
    if (!empty($CIDRAM['BlockInfo']['WhyReason'])) {
        $CIDRAM['BlockInfo']['WhyReason'] .= ', ';
    }
    $CIDRAM['BlockInfo']['WhyReason'] .= $CIDRAM['L10N']->getString('Short_Cloud') . $LN;
    if (!empty($CIDRAM['BlockInfo']['Signatures'])) {
        $CIDRAM['BlockInfo']['Signatures'] .= ', ';
    }
    $CIDRAM['BlockInfo']['Signatures'] .= $Factors[$FactorIndex];
    $CIDRAM['BlockInfo']['SignatureCount']++;
};

/** Execute object. */
$RunExitCode = $CIDRAM['RunParamResCache']['rules_specific.php']($Factors, $FactorIndex, $LN, $Tag);
