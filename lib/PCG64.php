<?php declare(strict_types = 1);

namespace PCG;

class PCG64
{
    private $state;
    private $sequence;

    function __construct()
    {
        $this->state = $this->unpack128(-7521967296361390075, 0x7d3e9cb6cfe0549b);
        $this->sequence = $this->unpack128(1, -2720673578348880933);
    }

    private function unpack128(int $hi, int $lo): array
    {
        return [
            $lo & 0x3ffffff,
            ($lo >> 26) & 0x3ffffff,
            (($lo >> 52) & 0xfff) | (($hi & 0x3fff) << 12),
            ($hi >> 14) & 0x3ffffff,
            ($hi >> 40) & 0x0ffffff
        ];
    }

    function srandom(int $state_hi, int $state_lo, int $sequence_hi, int $sequence_lo)
    {
        $tmpSeq = $this->unpack128($sequence_hi, $sequence_lo);
        $tmpState = $this->unpack128($state_hi, $state_lo);
        
        $this->state = [0, 0, 0, 0, 0];
        
        $this->sequence = [
            (($tmpSeq[0] << 1) | (                     1)) & 0x3ffffff,
            (($tmpSeq[1] << 1) | (($tmpSeq[0] >> 63) & 1)) & 0x3ffffff,
            (($tmpSeq[2] << 1) | (($tmpSeq[1] >> 63) & 1)) & 0x3ffffff,
            (($tmpSeq[3] << 1) | (($tmpSeq[2] >> 63) & 1)) & 0x3ffffff,
            (($tmpSeq[4] << 1) | (($tmpSeq[3] >> 63) & 1)) & 0x0ffffff
        ];
        
        $this->random();

        $this->state = [
            ($c = ($this->state[0] + $tmpState[0]             )) & 0x3ffffff,
            ($c = ($this->state[1] + $tmpState[1] + ($c >> 26))) & 0x3ffffff,
            ($c = ($this->state[2] + $tmpState[2] + ($c >> 26))) & 0x3ffffff,
            ($c = ($this->state[3] + $tmpState[3] + ($c >> 26))) & 0x3ffffff,
            (     ($this->state[4] + $tmpState[4] + ($c >> 26))) & 0x0ffffff,
        ];

        $this->random();
    }

    function random(): int
    {
        $previousState = $this->state;
        list($s0, $s1, $s2, $s3, $s4) = $previousState;
        list($i0, $i1, $i2, $i3, $i4) = $this->sequence;

        // This is 128-bit unsigned int multiplication equivalent to:
        // $this->state = $oldState * 0x2360ed051fc65da44385df649fccf645 + $this->sequence;
        $this->state = [
            ($c = $i0 + $s0 * 0x3ccf645                                                                                     ) & 0x3ffffff,
            ($c = $i1 + $s1 * 0x3ccf645 + $s0 * 0x177d927 +                                                       ($c >> 26)) & 0x3ffffff,
            ($c = $i2 + $s2 * 0x3ccf645 + $s1 * 0x177d927 + $s0 * 0x1da4438 +                                     ($c >> 26)) & 0x3ffffff,
            ($c = $i3 + $s3 * 0x3ccf645 + $s2 * 0x177d927 + $s1 * 0x1da4438 + $s0 * 0x0147f19 +                   ($c >> 26)) & 0x3ffffff,
            (     $i4 + $s4 * 0x3ccf645 + $s3 * 0x177d927 + $s2 * 0x1da4438 + $s1 * 0x0147f19 + $s0 * 0x02360ed + ($c >> 26)) & 0x0ffffff
        ];

        return $this->xsl_rr_128_64($previousState);
    }

    private function ror64(int $value, int $amount): int
    {
        return $amount === 0 ? $value :
            (($value >> $amount) & (PHP_INT_MAX >> ($amount - 1))) | ($value << (-$amount & 63));
    }

    private function xsl_rr_128_64(array $state): int
    {
        return $this->ror64(
            (($state[2] << 52) | ($state[1] << 26) | $state[0]) ^
            (($state[4] << 40) | ($state[3] << 14) | ($state[2] >> 12)),
            $state[4] >> 18
        );
    }
}
