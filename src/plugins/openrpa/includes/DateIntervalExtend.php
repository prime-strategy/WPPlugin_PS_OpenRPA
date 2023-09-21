<?php
declare(strict_types=1);

namespace PrimeStrategy\WP_Plugin\PS_OpenRPA;

class DateIntervalExtend extends \DateInterval
{
	/**
	 * 曜日。複数の曜日を複合で指定可能。
	 * 例: 99W は 0b0110011
	 */
	public int $w;

	public function __construct( string $duration )
	{
		preg_match( '/^(P.*?)(?:([0-9]{1,3})W)?([^W]*?)$/', $duration, $matches, PREG_OFFSET_CAPTURE );

		parent::__construct( $matches[1][0] . $matches[3][0] );

		$this->w = (int) $matches[2][0];
	}
}
