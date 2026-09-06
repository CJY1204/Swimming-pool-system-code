<?php
/**
 * Stores PHP session data in the `sessions_store` MySQL table instead of
 * each EC2 instance's local disk.
 *
 * Why this matters: once you put this app behind an Application Load
 * Balancer with an Auto Scaling Group (2+ EC2 instances), the ALB can
 * route a single user's requests to a *different* instance on every
 * click. Default PHP sessions live on local disk, so instance B has no
 * idea instance A logged that user in — they'd get randomly logged out.
 *
 * Storing sessions in RDS instead means every instance reads/writes the
 * same session data, so login state (member_id / admin_id) stays correct
 * no matter which instance handles the request — no ALB "stickiness"
 * setting required.
 */
class DbSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private int $lifetimeSeconds;

    public function __construct(PDO $pdo, int $lifetimeSeconds = 1440)
    {
        $this->pdo = $pdo;
        $this->lifetimeSeconds = $lifetimeSeconds;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions_store WHERE id = ? AND expires_at > NOW()");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $this->lifetimeSeconds);
        $stmt = $this->pdo->prepare("
            INSERT INTO sessions_store (id, data, expires_at)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)
        ");
        return $stmt->execute([$id, $data, $expiresAt]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions_store WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions_store WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
