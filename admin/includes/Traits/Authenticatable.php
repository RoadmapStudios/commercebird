<?php

declare(strict_types=1);

namespace RMS\Admin\Traits;

trait Authenticatable {
	public function authenticate( string $request_content, string $webhook_secret ): bool {
		$matches = array();
		$matched = preg_match( '/^{"Content":(.*),"HashCode":"(.*)"}$/', $request_content, $matches );

		if ( $matched === 1 && isset( $matches[1] ) && isset( $matches[2] ) ) {
			return $matches[2] === strtoupper( hash_hmac( 'sha256', $matches[1], $webhook_secret ) );
		}

		return false;
	}
}
