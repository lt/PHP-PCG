<?php declare(strict_types = 1);

namespace PCG;

class PCG32
{
    private $state = -8846114313915602277; // 0x853c49e6748fea9b
    private $sequence = -2720673578348880933; // 0xda3e39cb94b95bdb

    function srandom(int $state, int $sequence)
    {
        $this->state = 0;
        $this->sequence = ($sequence << 1) | 1;
        $this->random();
        $this->state += $state;
        $this->random();
    }

    function random(): int
    {
        $previousState = $this->state;

        // This is 64-bit unsigned int multiplication equivalent to:
        // $this->state = $previousState * 6364136223846793005 + $this->sequence;
        // 0x4c957f2d * 0xffffffff, 0x5851f42d * 0xffffffff
        // are both less than 2**63, so it is safe to use 32 bit operands

        $prev0 = $previousState & 0xffffffff;
        $prev1 = ($previousState >> 32) & 0xffffffff;

        $carry = (0x4c957f2d * $prev0) + ($this->sequence & 0xffffffff);
        $prev1 = ((0x4c957f2d * $prev1) & 0xffffffff) +
                ((0x5851f42d * $prev0) & 0xffffffff) +
                (($carry >> 32) & 0xffffffff) +
                (($this->sequence >> 32) & 0xffffffff);

        $this->state = ($prev1 << 32) | ($carry & 0xffffffff);

        return $this->xsh_rr_64_32($previousState);
    }

    private function ror32(int $value, int $amount): int
    {
        return (($value & 0xffffffff) >> $amount) | ($value << (-$amount & 31)) & 0xffffffff;
    }

    private function xsh_rr_64_32(int $state): int
    {
        $s18 = ($state >> 18) & (0x7fffffffffffffff >> 17);
        $s59 = ($state >> 59) & (0x7fffffffffffffff >> 58);

        // ror32 will discard the upper bits so no need to mask here.
        return $this->ror32((($s18 ^ $state) >> 27), $s59);
    }
}
