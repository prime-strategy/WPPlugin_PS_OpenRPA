<?php
declare(strict_types=1);

namespace PrimeStrategy\WP_Plugin\PS_OpenRPA;

final class DateIntervalExtend extends \DateInterval {

	/**
	 * 曜日。複数の曜日を複合で指定可能。
	 * 例: 99A は 0b0110011
	 */
	public int $a;

	public function __construct( string $duration ) {
		preg_match( '/^(P.*?)(?:(\d{1,3})A)?([^A]*?)$/', $duration, $matches, PREG_OFFSET_CAPTURE );

		parent::__construct( $matches[1][0] . $matches[3][0] );

		$this->a = (int) $matches[2][0];
	}

	/**
	 * 選択された曜日フラグから、次の曜日の日付を取得する。
	 *
	 * @param $datetime 基準日
	 * @param $select_weekday 週のうち選択された曜日ビットフラグ。1桁目:日 ～ 7桁目:土。0b0000001 から 0b1111111 の範囲。
	 */
	public function get_next_in_weekday( \DateTimeInterface $datetime, int $select_weekday ): \DateTimeInterface {
		$weekday      = (int) $datetime->format( 'w' ); // 0 (Sun) ... 6 (Sat)
		$next_day     = $this->calc_next( $weekday, $select_weekday, 7 );
		$dateInterval = new \DateInterval( "P{$next_day}D" );

		return $datetime->add( $dateInterval );
	}

	/**
	 * ビットフラグから基準値の次のフラグが 1 になっている上位ビットの桁の差を取得.
	 *
	 * @param $num 基準フラグ桁. 1 以上の値.
	 * @param $flags ビットフラグ. 1 以上の値.
	 * @param $flags_len ビットフラグ最大値の桁数. 1 以上 CPU ビット数 (一般的には 64) 未満の値.
	 *
	 * @return int 次のフラグが 1 になっている上位ビットの桁までの差
	 */
	public function next_bitflag( int $num, int $flags, int $flags_len ): int {
		if ( $num <= 0 || $flags <= 0 || $flags_len <= 0 ) {
			throw new \UnderflowException();
		}

		if ( $num > $flags_len || $flags >> $flags_len > 0 || $flags_len >= ( PHP_INT_SIZE * 8 ) ) {
			throw new \OverflowException();
		}

		for ( $i = 0; $i < $flags_len; ++$i ) {
			$mask = 0b1 << ( $num + $i ) % $flags_len;

			if ( ( $flags & $mask ) === $mask ) {
				return $i + 1;
			}
		}
	}
}
