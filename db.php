<?php
/**
 * Database Connection Module
 * Connects to MongoDB Atlas cluster using the official MongoDB PHP Library (mongodb/mongodb)
 * Features an intelligent fallback to ensure Step 5 testing works out-of-the-box
 * even before pasting Atlas credentials.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Connection state flags
$mongoConnected = false;
$mongoErrorMessage = null;
$dbInstance = null;

// Initialize collections
$usersCollection = null;
$profilesCollection = null;
$jobsCollection = null;
$applicationsCollection = null;

$uri = MONGODB_URI;
$dbName = MONGODB_DB_NAME;

// Check if credentials are still placeholder
$isPlaceholder = (
    strpos($uri, '<username>') !== false || 
    strpos($uri, '<password>') !== false ||
    empty($uri)
);

if (!$isPlaceholder) {
    try {
        // Attempt connection with Atlas cluster
        $client = new Client($uri, [], [
            'serverSelectionTimeoutMS' => 2500, // Fast timeout for responsive UI
        ]);
        
        // Ping database to confirm handshake
        $client->selectDatabase('admin')->command(['ping' => 1]);
        
        $dbInstance = $client->selectDatabase($dbName);
        $usersCollection = $dbInstance->selectCollection('users');
        $profilesCollection = $dbInstance->selectCollection('profiles');
        $jobsCollection = $dbInstance->selectCollection('jobs');
        $applicationsCollection = $dbInstance->selectCollection('applications');
        
        $mongoConnected = true;
    } catch (\Throwable $e) {
        $mongoConnected = false;
        $mongoErrorMessage = $e->getMessage();
    }
}

// Fallback driver to guarantee local dev testing works seamlessly
if (!$mongoConnected) {
    /**
     * Local JSON-backed Mock Collection conforming to MongoDB PHP Library interface
     */
    class LocalMongoCollection {
        private string $name;
        private string $filePath;

        public function __construct(string $name) {
            $this->name = $name;
            $dataDir = __DIR__ . '/data';
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0777, true);
            }
            $this->filePath = $dataDir . '/' . $name . '.json';
            if (!file_exists($this->filePath)) {
                file_put_contents($this->filePath, json_encode([], JSON_PRETTY_PRINT));
            }
        }

        private function loadData(): array {
            if (!file_exists($this->filePath)) {
                return [];
            }
            $content = file_get_contents($this->filePath);
            $data = json_decode($content, true);
            return is_array($data) ? $data : [];
        }

        private function saveData(array $data): void {
            file_put_contents($this->filePath, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        private function match(array $doc, array $filter): bool {
            foreach ($filter as $key => $val) {
                if ($key === '_id') {
                    $docId = (string)($doc['_id'] ?? '');
                    $valId = (string)$val;
                    if ($docId !== $valId) return false;
                    continue;
                }

                if (is_array($val) && isset($val['$lte'])) {
                    if (!isset($doc[$key]) || (float)$doc[$key] > (float)$val['$lte']) return false;
                    continue;
                }
                if (is_array($val) && isset($val['$gte'])) {
                    if (!isset($doc[$key]) || (float)$doc[$key] < (float)$val['$gte']) return false;
                    continue;
                }
                if (is_array($val) && isset($val['$in'])) {
                    if (!isset($doc[$key]) || !in_array($doc[$key], (array)$val['$in'])) return false;
                    continue;
                }
                if (is_array($val) && isset($val['$ne'])) {
                    if (isset($doc[$key]) && $doc[$key] === $val['$ne']) return false;
                    continue;
                }

                if (!array_key_exists($key, $doc) || (string)$doc[$key] !== (string)$val) {
                    return false;
                }
            }
            return true;
        }

        public function insertOne($document) {
            $data = $this->loadData();
            $doc = (array)$document;
            if (!isset($doc['_id'])) {
                $doc['_id'] = (string)new ObjectId();
            } else {
                $doc['_id'] = (string)$doc['_id'];
            }
            if (!isset($doc['created_at'])) {
                $doc['created_at'] = date('Y-m-d H:i:s');
            }
            $data[] = $doc;
            $this->saveData($data);

            return new class($doc['_id']) {
                private $insertedId;
                public function __construct($id) { $this->insertedId = $id; }
                public function getInsertedId() { return $this->insertedId; }
            };
        }

        public function findOne(array $filter = [], array $options = []): ?array {
            $data = $this->loadData();
            foreach ($data as $doc) {
                if ($this->match($doc, $filter)) {
                    return $doc;
                }
            }
            return null;
        }

        public function find(array $filter = [], array $options = []): array {
            $data = $this->loadData();
            $results = [];
            foreach ($data as $doc) {
                if ($this->match($doc, $filter)) {
                    $results[] = $doc;
                }
            }

            // Handle sort option
            if (isset($options['sort']) && is_array($options['sort'])) {
                foreach ($options['sort'] as $field => $dir) {
                    usort($results, function($a, $b) use ($field, $dir) {
                        $valA = $a[$field] ?? null;
                        $valB = $b[$field] ?? null;
                        if ($valA == $valB) return 0;
                        if ($dir == -1) {
                            return ($valA < $valB) ? 1 : -1;
                        }
                        return ($valA > $valB) ? 1 : -1;
                    });
                    break;
                }
            }

            // Handle limit option
            if (isset($options['limit']) && is_numeric($options['limit'])) {
                $results = array_slice($results, 0, (int)$options['limit']);
            }

            return $results;
        }

        public function updateOne(array $filter, array $update, array $options = []) {
            $data = $this->loadData();
            $modifiedCount = 0;
            foreach ($data as $i => $doc) {
                if ($this->match($doc, $filter)) {
                    if (isset($update['$set'])) {
                        foreach ($update['$set'] as $k => $v) {
                            $data[$i][$k] = $v;
                        }
                    } else {
                        foreach ($update as $k => $v) {
                            $data[$i][$k] = $v;
                        }
                    }
                    $data[$i]['updated_at'] = date('Y-m-d H:i:s');
                    $modifiedCount = 1;
                    break;
                }
            }
            if ($modifiedCount > 0) {
                $this->saveData($data);
            }

            return new class($modifiedCount) {
                private $matchedCount;
                public function __construct($c) { $this->matchedCount = $c; }
                public function getModifiedCount() { return $this->matchedCount; }
                public function getMatchedCount() { return $this->matchedCount; }
            };
        }

        public function deleteOne(array $filter) {
            $data = $this->loadData();
            $deletedCount = 0;
            foreach ($data as $i => $doc) {
                if ($this->match($doc, $filter)) {
                    array_splice($data, $i, 1);
                    $deletedCount = 1;
                    break;
                }
            }
            if ($deletedCount > 0) {
                $this->saveData($data);
            }

            return new class($deletedCount) {
                private $deletedCount;
                public function __construct($c) { $this->deletedCount = $c; }
                public function getDeletedCount() { return $this->deletedCount; }
            };
        }

        public function countDocuments(array $filter = []): int {
            return count($this->find($filter));
        }
    }

    $usersCollection = new LocalMongoCollection('users');
    $profilesCollection = new LocalMongoCollection('profiles');
    $jobsCollection = new LocalMongoCollection('jobs');
    $applicationsCollection = new LocalMongoCollection('applications');
}

/**
 * Normalizes document IDs to string
 */
function get_id_string($docOrId): string {
    if (is_array($docOrId) && isset($docOrId['_id'])) {
        return (string)$docOrId['_id'];
    }
    if (is_object($docOrId) && isset($docOrId->_id)) {
        return (string)$docOrId->_id;
    }
    return (string)$docOrId;
}

/**
 * Returns connection diagnostic info for UI banners
 */
function get_db_status(): array {
    global $mongoConnected, $isPlaceholder, $mongoErrorMessage;
    return [
        'is_connected' => $mongoConnected,
        'is_placeholder' => $isPlaceholder,
        'error' => $mongoErrorMessage,
        'driver' => $mongoConnected ? 'MongoDB Atlas (Live)' : 'Local NoSQL Engine (Demo Mode)',
        'target_db' => MONGODB_DB_NAME
    ];
}

// Automatically seed sample data if empty so testing is immediately interactive
require_once __DIR__ . '/includes/seed.php';
seed_initial_data_if_needed();
