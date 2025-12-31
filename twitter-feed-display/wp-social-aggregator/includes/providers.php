<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Very small provider helpers. These try oEmbed when a URL is provided, return embed HTML when present,
 * and fall back to a simple link for handles.
 */

function wsa_normalize_sources( $sources_str ) {
    $items = array();
    $parts = preg_split('/[,\n]+/', $sources_str);
    foreach ( $parts as $p ) {
        $p = trim( $p );
        if ( ! $p ) continue;
        $items[] = $p;
    }
    return $items;
}

function wsa_fetch_for_network( $network, $sources_str, $embed_html = '', $limit = 5 ) {
    $out = array();
    if ( ! empty( $embed_html ) ) {
        $out[] = array( 'network' => $network, 'content' => $embed_html );
        return $out;
    }

    $sources = wsa_normalize_sources( $sources_str );
    foreach ( $sources as $s ) {
        if ( count( $out ) >= $limit ) break;
        // If looks like URL, attempt oEmbed
        if ( filter_var( $s, FILTER_VALIDATE_URL ) ) {
            $html = wp_oembed_get( $s );
            if ( $html ) {
                $out[] = array( 'network' => $network, 'content' => $html );
                continue;
            }
        }

        // If not URL, treat as handle or ID -> render a link
        if ( $network === 'twitter' ) {
            $handle = ltrim( $s, '@' );
            $url = 'https://twitter.com/' . esc_attr( $handle );
            $out[] = array( 'network' => $network, 'content' => '<a href="' . esc_url( $url ) . '" target="_blank">@' . esc_html( $handle ) . '</a>' );
            continue;
        }

        // Generic link for other networks
        $out[] = array( 'network' => $network, 'content' => '<a href="' . esc_attr( $s ) . '" target="_blank">' . esc_html( $s ) . '</a>' );
    }

    return $out;
}
