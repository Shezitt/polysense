<?php

namespace App\Utils;

/**
 * Simple in-memory priority queue with explicit string priorities.
 * Preserves FIFO order within the same priority level.
 * Priorities default order: high, medium, low.
 */
class PriorityQueue
{
    /** @var array<int,string> */
    protected array $prioritiesOrder = ['high', 'medium', 'low'];

    /** @var array<string,array> */
    protected array $queues = [];

    public function __construct(array $priorities = null)
    {
        $this->prioritiesOrder = $priorities ?? $this->prioritiesOrder;
        foreach ($this->prioritiesOrder as $p) {
            $this->queues[$p] = [];
        }
    }

    /**
     * Enqueue an item with given priority (defaults to 'medium').
     * @param mixed $item
     */
    public function enqueue($item, string $priority = 'medium'): void
    {
        if (!array_key_exists($priority, $this->queues)) {
            // If unknown priority, fall back to medium
            $priority = 'medium';
            if (!array_key_exists($priority, $this->queues)) {
                // If priorities were customized and medium not present, use first defined
                $priority = $this->prioritiesOrder[0];
            }
        }

        $this->queues[$priority][] = $item;
    }

    /**
     * Dequeue next item following priority order (highest first). Returns null if empty.
     * @return mixed|null
     */
    public function dequeue()
    {
        foreach ($this->prioritiesOrder as $p) {
            if (!empty($this->queues[$p])) {
                return array_shift($this->queues[$p]);
            }
        }

        return null;
    }

    /**
     * Peek next item without removing, or null if empty.
     * @return mixed|null
     */
    public function peek()
    {
        foreach ($this->prioritiesOrder as $p) {
            if (!empty($this->queues[$p])) {
                return $this->queues[$p][0];
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        foreach ($this->queues as $q) {
            if (!empty($q)) return false;
        }
        return true;
    }

    public function size(): int
    {
        $count = 0;
        foreach ($this->queues as $q) {
            $count += count($q);
        }
        return $count;
    }

    /**
     * Drain all items in priority order and return them as an array.
     * @return array
     */
    public function drain(): array
    {
        $out = [];
        while (!$this->isEmpty()) {
            $out[] = $this->dequeue();
        }
        return $out;
    }

    /**
     * Clear all queues.
     */
    public function clear(): void
    {
        foreach ($this->prioritiesOrder as $p) {
            $this->queues[$p] = [];
        }
    }
}
