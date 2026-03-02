<?php
/**
 * Manga Service Layer
 * 
 * Encapsulates business logic for manga operations
 */

class MangaService {
    private $conn;
    private $logger;

    public function __construct(mysqli $conn, $logger = null) {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    /**
     * Search manga by title or genre
     */
    public function search($query, $limit = 20) {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $searchTerm = '%' . $this->conn->real_escape_string($query) . '%';
        
        $sql = "SELECT id, title, category, status, last_chapter 
                FROM manga 
                WHERE LOWER(title) LIKE LOWER(?) 
                   OR LOWER(category) LIKE LOWER(?)
                ORDER BY title ASC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limit);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get manga by ID with full details
     */
    public function getById($id) {
        $sql = "SELECT * FROM manga WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all manga with optional filtering and sorting
     */
    public function getAll($filters = [], $sort = 'id DESC', $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM manga WHERE 1=1";
        $types = "";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $types .= "s";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND LOWER(category) LIKE LOWER(?)";
            $types .= "s";
            $params[] = '%' . $filters['category'] . '%';
        }

        if (!empty($filters['search'])) {
            $sql .= " AND LOWER(title) LIKE LOWER(?)";
            $types .= "s";
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY " . preg_replace('/[^a-zA-Z0-9_,\s]/', '', $sort);
        $sql .= " LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get unique genres
     */
    public function getGenres() {
        $sql = "SELECT DISTINCT category FROM manga WHERE category IS NOT NULL AND category != '' ORDER BY category";
        $result = $this->conn->query($sql);
        
        $genres = [];
        while ($row = $result->fetch_assoc()) {
            $cats = array_map('trim', explode(',', $row['category']));
            foreach ($cats as $cat) {
                if (!empty($cat) && !in_array($cat, $genres)) {
                    $genres[] = $cat;
                }
            }
        }
        
        sort($genres);
        return $genres;
    }

    /**
     * Get statistics
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'currently reading' THEN 1 END) as reading,
                    COUNT(CASE WHEN status = 'finished' THEN 1 END) as finished,
                    COUNT(CASE WHEN status = 'will read' THEN 1 END) as will_read,
                    COUNT(CASE WHEN status = 'dropped' THEN 1 END) as dropped
                FROM manga";
        
        return $this->conn->query($sql)->fetch_assoc();
    }

    /**
     * Create manga entry
     */
    public function create($data) {
        $required = ['title', 'category', 'status'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $sql = "INSERT INTO manga (title, category, status, last_chapter, read_link, external_links) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssss",
            $data['title'],
            $data['category'],
            $data['status'],
            $data['last_chapter'] ?? null,
            $data['read_link'] ?? null,
            json_encode($data['external_links'] ?? [])
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        throw new Exception("Failed to create manga");
    }

    /**
     * Update manga entry
     */
    public function update($id, $data) {
        $allowed = ['title', 'category', 'status', 'last_chapter', 'read_link', 'external_links'];
        $updates = [];
        $types = "";
        $params = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $types .= ($field === 'external_links') ? "s" : "s";
                $params[] = ($field === 'external_links') ? json_encode($data[$field]) : $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE manga SET " . implode(", ", $updates) . " WHERE id = ?";
        $types .= "i";
        $params[] = $id;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        return $stmt->execute();
    }

    /**
     * Delete manga entry
     */
    public function delete($id) {
        $sql = "DELETE FROM manga WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    /**
     * Log action
     */
    private function log($action, $details = "") {
        if ($this->logger) {
            $this->logger->info("MangaService: $action - $details");
        }
    }
}

?>
