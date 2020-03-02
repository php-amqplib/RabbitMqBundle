<?php

namespace OldSound\RabbitMqBundle\MemoryChecker;

/**
 * Help handling memory limits .
 *
 * @author Jonas Haouzi <jonas@viscaweb.com>
 */
class MemoryConsumptionChecker
{
    /** @var NativeMemoryUsageProvider */
    private $memoryUsageProvider;

    /**
     * MemoryManager constructor.
     *
     * @param NativeMemoryUsageProvider $memoryUsageProvider
     */
    public function __construct(NativeMemoryUsageProvider $memoryUsageProvider) {
        $this->memoryUsageProvider = $memoryUsageProvider;
    }

    /**
     * @param int|string $allowedConsumptionUntil
     * @param int|string $maxConsumptionAllowed
     *
     * @return bool
     */
    public function isRamAlmostOverloaded($maxConsumptionAllowed, $allowedConsumptionUntil = 0)
    {
        $allowedConsumptionUntil = $this->convertHumanUnitToNumerical($allowedConsumptionUntil);
        $maxConsumptionAllowed = $this->convertHumanUnitToNumerical($maxConsumptionAllowed);
        $currentUsage = $this->convertHumanUnitToNumerical($this->memoryUsageProvider->getMemoryUsage());

        return $currentUsage > ($maxConsumptionAllowed - $allowedConsumptionUntil);
    }

    /**
     * @param int|string $humanUnit
     *
     * @return int
     */
    private function convertHumanUnitToNumerical($humanUnit)
    {
        $numerical = $humanUnit;
        if (!is_numeric($humanUnit)) {
            $numerical = (int) substr($numerical, 0, -1);
            switch (substr($humanUnit, -1)) {
                case 'G':
                    $numerical *= pow(1024, 3);
                    break;
                case 'M':
                    $numerical *= pow(1024, 2);
                    break;
                case 'K':
                    $numerical *= 1024;
                    break;
            }
        }

        return (int)$numerical;
    }

}
