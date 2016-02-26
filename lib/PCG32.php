<?php declare(strict_types = 1);

namespace PCG;

class PCG32
{
    private $rng = [
        -8846114313915602277, // 0x853c49e6748fea9b
        -2720673578348880933  // 0xda3e39cb94b95bdb
    ];

    function srandom(int $state, int $sequence)
    {
        $this->rng[0] = 0;
        $this->rng[1] = ($sequence << 1) | 1;
        $this->random();
        $this->rng[0] += $state;
        $this->random();
    }

    function random(): int
    {
        $oldState = $this->rng[0];

        // This is 64-bit unsigned int multiplication equivalent to:
        // $this->rng[0] = $oldState * 6364136223846793005 + $this->rng[1];
        $old0 = $oldState & 0xffffffff;
        $old1 = ($oldState >> 32) & 0xffffffff;

        $carry = (0x4c957f2d * $old0) + $this->rng[1];
        $old1 = ((0x4c957f2d * $old1) & 0xffffffff) +
                ((0x5851f42d * $old0) & 0xffffffff) +
                (($carry >> 32) & 0xffffffff);

        $this->rng[0] = ($old1 << 32) | ($carry & 0xffffffff);

        return $this->xsh_rr_64_32($oldState);
    }

    private function ror32(int $value, int $amount): int
    {
        return (($value & 0xffffffff) >> $amount) | ($value << (-$amount & 31)) & 0xffffffff;
    }

    private function xsh_rr_64_32(int $state): int
    {
        $s18 = ($state >> 18) & (0x7fffffffffffffff >> 17);
        $s59 = ($state >> 59) & (0x7fffffffffffffff >> 58);

        // $s18 already has any sign extension masked away, so no need to mask this shift.
        return $this->ror32((($s18 ^ $state) >> 27), $s59);
    }
}