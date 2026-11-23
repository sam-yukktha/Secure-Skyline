<?php
declare(strict_types=1);

require_once __DIR__ . '/KMeans/Cluster.php';
require_once __DIR__ . '/KMeans/Config.php';

/**
 * Small, deterministic two-dimensional K-Means implementation retained for the
 * project's original educational clustering feature. It is not a trained model.
 */
class KMeans
{
    private array $_data = [];
    private array $_clusters = [];
    private ?KMeans_Config $_config = null;

    public function setData(array $data): self { $this->_data = array_values($data); return $this; }
    public function setXKey(mixed $key): self { $this->getConfig()->setXKey($key); return $this; }
    public function setYKey(mixed $key): self { $this->getConfig()->setYKey($key); return $this; }
    public function setClusterCount(int $count): self { $this->getConfig()->setClusterCount(max(1, $count)); return $this; }
    public function getClusters(): array { return $this->_clusters; }
    public function getConfig(): KMeans_Config { return $this->_config ??= new KMeans_Config(); }
    public function toArray(): array { return array_map(static fn(KMeans_Cluster $cluster) => $cluster->toArray(), $this->_clusters); }

    public function solve(): self
    {
        $this->_clusters = [];
        if (!$this->_data) { return $this; }
        $xKey = $this->getConfig()->getXKey(); $yKey = $this->getConfig()->getYKey();
        foreach ($this->_data as $point) {
            if (!isset($point[$xKey], $point[$yKey]) || !is_numeric($point[$xKey]) || !is_numeric($point[$yKey])) {
                throw new InvalidArgumentException('K-Means input points must contain numeric X and Y values.');
            }
        }
        usort($this->_data, static fn($a, $b) => (($a[$xKey] + $a[$yKey]) <=> ($b[$xKey] + $b[$yKey])));
        $k = min($this->getConfig()->getClusterCount(), count($this->_data));
        for ($i = 0; $i < $k; $i++) {
            $seedIndex = $k === 1 ? 0 : (int) round($i * (count($this->_data) - 1) / ($k - 1));
            $seed = $this->_data[$seedIndex];
            $this->_clusters[] = (new KMeans_Cluster($this->getConfig()))->setX((float)$seed[$xKey])->setY((float)$seed[$yKey]);
        }
        $previous = null;
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $assignments = array_fill(0, $k, []);
            foreach ($this->_data as $point) {
                $bestIndex = 0; $bestDistance = INF;
                foreach ($this->_clusters as $index => $cluster) {
                    $distance = (($point[$xKey] - $cluster->getX()) ** 2) + (($point[$yKey] - $cluster->getY()) ** 2);
                    if ($distance < $bestDistance) { $bestDistance = $distance; $bestIndex = $index; }
                }
                $assignments[$bestIndex][] = $point;
            }
            $signature = serialize(array_map(static fn($points) => array_column($points, 'id'), $assignments));
            foreach ($this->_clusters as $index => $cluster) { $cluster->setData($assignments[$index])->updateCentroid(); }
            if ($signature === $previous) { break; }
            $previous = $signature;
        }
        return $this;
    }
}
