<?php

namespace App\Models;

use App\Core\Model;

class Page extends Model
{
    protected string $table = 'pages';

    public function getActive(): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC, title ASC"
        );
    }

    public function getFooterPages(): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE is_active = 1 AND show_in_footer = 1 ORDER BY sort_order ASC, title ASC"
        );
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE slug = ? AND is_active = 1",
            [$slug]
        );
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $result = $this->db->selectOne(
                "SELECT id FROM {$this->table} WHERE slug = ? AND id != ?",
                [$slug, $excludeId]
            );
        } else {
            $result = $this->db->selectOne(
                "SELECT id FROM {$this->table} WHERE slug = ?",
                [$slug]
            );
        }
        return $result !== false;
    }
}
