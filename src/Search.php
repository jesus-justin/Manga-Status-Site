<?php
/**
 * Search Helper Class
 * 
 * Provides search and filtering utilities.
 */

class Search {
    private $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Search manga by title
     */
    public function searchMangaByTitle($query, $limit = 20) {
        $query = $this->conn->real_escape_string($query);
        $sql = "SELECT * FROM manga WHERE LOWER(title) LIKE LOWER('%$query%') LIMIT $limit";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Search manga by genre
     */
    public function searchByGenre($genre, $limit = 20) {
        $genre = $this->conn->real_escape_string($genre);
        $sql = "SELECT * FROM manga WHERE category LIKE '%$genre%' LIMIT $limit";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Search manga by status
     */
    public function searchByStatus($status, $limit = 20) {
        $status = $this->conn->real_escape_string($status);
        $sql = "SELECT * FROM manga WHERE status = '$status' LIMIT $limit";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Advanced search
     */
    public function advancedSearch($filters = []) {
        $sql = "SELECT * FROM manga WHERE 1=1";
        
        if (!empty($filters['title'])) {
            $title = $this->conn->real_escape_string($filters['title']);
            $sql .= " AND LOWER(title) LIKE LOWER('%$title%')";
        }
        
        if (!empty($filters['status'])) {
            $status = $this->conn->real_escape_string($filters['status']);
            $sql .= " AND status = '$status'";
        }
        
        if (!empty($filters['genre'])) {
            $genre = $this->conn->real_escape_string($filters['genre']);
            $sql .= " AND category LIKE '%$genre%'";
        }
        
        if (!empty($filters['orderBy'])) {
            $orderBy = $this->conn->real_escape_string($filters['orderBy']);
            $direction = !empty($filters['orderDirection']) && strtoupper($filters['orderDirection']) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $orderBy $direction";
        }
        
        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Search users by username or email
     */
    public function searchUsers($query, $limit = 20) {
        $query = $this->conn->real_escape_string($query);
        $sql = "SELECT * FROM users WHERE LOWER(username) LIKE LOWER('%$query%') OR LOWER(email) LIKE LOWER('%$query%') LIMIT $limit";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get trending manga (most added)
     */
    public function getTrendingManga($days = 30, $limit = 10) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $sql = "SELECT manga.*, COUNT(uc.id) as collection_count 
                FROM manga 
                LEFT JOIN user_collections uc ON manga.id = uc.manga_id AND uc.added_date > '$date'
                GROUP BY manga.id 
                ORDER BY collection_count DESC 
                LIMIT $limit";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get popular genres
     */
    public function getPopularGenres($limit = 10) {
        $sql = "SELECT 
                    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(category, ',', numbers.n), ',', -1)) as genre,
                    COUNT(*) as count
                FROM manga
                JOIN (SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) numbers
                WHERE CHAR_LENGTH(category) - CHAR_LENGTH(REPLACE(category, ',', '')) >= numbers.n - 1
                GROUP BY genre
                ORDER BY count DESC
                LIMIT $limit";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

?>
