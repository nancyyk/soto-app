<?php

/**
 * Point Engine Tests
 *
 * Tests the FSM-based bottle counting logic and point calculation.
 * Validates anti-double-count behavior across all state transitions.
 *
 * FSM States: Idle -> Stage1 (IR1 triggered) -> Stage2 (IR2 triggered) -> Stage3 (IR1 cleared) -> Idle (IR2 cleared = bottle counted)
 * Cancel path: If IR2 cleared before IR1 cleared, it is a withdrawal — no point awarded.
 */

/**
 * Simple FSM implementation representing the ESP32 dual-IR state machine
 * mirrored in PHP for testing purposes.
 */
class BottleFsm
{
    const STATE_IDLE = 'idle';

    const STATE_STAGE1 = 'stage1';

    const STATE_STAGE2 = 'stage2';

    const STATE_STAGE3 = 'stage3';

    private string $state = self::STATE_IDLE;

    private int $count = 0;

    private int $poinPerBottle;

    public function __construct(int $poinPerBottle = 10)
    {
        $this->poinPerBottle = $poinPerBottle;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTotalPoin(): int
    {
        return $this->count * $this->poinPerBottle;
    }

    public function ir1Blocked(): void
    {
        if ($this->state === self::STATE_IDLE) {
            $this->state = self::STATE_STAGE1;
        }
    }

    public function ir2Blocked(): void
    {
        if ($this->state === self::STATE_STAGE1) {
            $this->state = self::STATE_STAGE2;
        }
    }

    public function ir1Cleared(): void
    {
        if ($this->state === self::STATE_STAGE2) {
            $this->state = self::STATE_STAGE3;
        }
    }

    public function ir2Cleared(): void
    {
        if ($this->state === self::STATE_STAGE3) {
            // Full insertion confirmed — count the bottle
            $this->count++;
            $this->state = self::STATE_IDLE;
        } elseif ($this->state === self::STATE_STAGE2) {
            // Bottle pulled back before clearing IR1 — withdrawal, no count
            $this->state = self::STATE_IDLE;
        }
    }

    public function reset(): void
    {
        $this->state = self::STATE_IDLE;
        $this->count = 0;
    }
}

describe('BottleFsm — state transitions', function () {

    beforeEach(function () {
        $this->fsm = new BottleFsm(poinPerBottle: 10);
    });

    it('starts in Idle state with zero count', function () {
        expect($this->fsm->getState())->toBe('idle');
        expect($this->fsm->getCount())->toBe(0);
    });

    it('transitions Idle -> Stage1 when IR1 blocked', function () {
        $this->fsm->ir1Blocked();
        expect($this->fsm->getState())->toBe('stage1');
    });

    it('transitions Stage1 -> Stage2 when IR2 blocked', function () {
        $this->fsm->ir1Blocked();
        $this->fsm->ir2Blocked();
        expect($this->fsm->getState())->toBe('stage2');
    });

    it('transitions Stage2 -> Stage3 when IR1 cleared', function () {
        $this->fsm->ir1Blocked();
        $this->fsm->ir2Blocked();
        $this->fsm->ir1Cleared();
        expect($this->fsm->getState())->toBe('stage3');
    });

    it('counts bottle and returns to Idle when IR2 cleared in Stage3', function () {
        $this->fsm->ir1Blocked();
        $this->fsm->ir2Blocked();
        $this->fsm->ir1Cleared();
        $this->fsm->ir2Cleared();

        expect($this->fsm->getState())->toBe('idle');
        expect($this->fsm->getCount())->toBe(1);
    });

    it('does NOT count when bottle is withdrawn (IR2 cleared before IR1 cleared)', function () {
        $this->fsm->ir1Blocked(); // bottle enters
        $this->fsm->ir2Blocked(); // bottle reaches inner sensor
        // Bottle withdrawn — IR2 clears before IR1
        $this->fsm->ir2Cleared(); // withdrawal path
        $this->fsm->ir1Cleared(); // IR1 eventually clears (in idle state, no effect)

        expect($this->fsm->getState())->toBe('idle');
        expect($this->fsm->getCount())->toBe(0); // no double-count
    });

    it('counts multiple bottles correctly without double-counting', function () {
        $insertBottle = function () {
            $this->fsm->ir1Blocked();
            $this->fsm->ir2Blocked();
            $this->fsm->ir1Cleared();
            $this->fsm->ir2Cleared();
        };

        $insertBottle();
        $insertBottle();
        $insertBottle();

        expect($this->fsm->getCount())->toBe(3);
        expect($this->fsm->getState())->toBe('idle');
    });

    it('calculates correct total poin', function () {
        // 3 bottles x 10 poin each = 30 poin
        for ($i = 0; $i < 3; $i++) {
            $this->fsm->ir1Blocked();
            $this->fsm->ir2Blocked();
            $this->fsm->ir1Cleared();
            $this->fsm->ir2Cleared();
        }
        expect($this->fsm->getTotalPoin())->toBe(30);
    });

    it('ignores IR1 blocked when not in Idle state', function () {
        $this->fsm->ir1Blocked(); // Stage1
        $this->fsm->ir1Blocked(); // double-trigger — should be ignored
        expect($this->fsm->getState())->toBe('stage1');
    });

    it('ignores IR2 blocked when not in Stage1', function () {
        $this->fsm->ir2Blocked(); // called without ir1Blocked first
        expect($this->fsm->getState())->toBe('idle'); // no change
    });
});

describe('Point calculation', function () {

    it('calculates poin correctly for different poin_per_botol values', function () {
        $fsm5 = new BottleFsm(poinPerBottle: 5);
        $fsm20 = new BottleFsm(poinPerBottle: 20);

        foreach ([$fsm5, $fsm20] as $fsm) {
            $fsm->ir1Blocked();
            $fsm->ir2Blocked();
            $fsm->ir1Cleared();
            $fsm->ir2Cleared();
        }

        expect($fsm5->getTotalPoin())->toBe(5);
        expect($fsm20->getTotalPoin())->toBe(20);
    });
});
