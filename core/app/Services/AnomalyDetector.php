<?php

namespace App\Services;

use App\Models\AuditLog;

class AnomalyDetector
{
    /**
     * Analyse a new audit log entry and return [is_suspicious, reason].
     */
    public function analyse(array $data): array
    {
        $reasons = [];

        // 1. Brute force: 5+ failed logins from same IP in 10 minutes
        if ($data['action'] === 'login.failed' && $data['ip_address']) {
            $recentFails = AuditLog::where('action', 'login.failed')
                ->where('ip_address', $data['ip_address'])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($recentFails >= 4) {
                $reasons[] = "Brute-force: {$recentFails} failed logins from this IP in 10 min";
            }
        }

        // 2. Login from new country (different from last 5 logins for this user)
        if ($data['action'] === 'login' && isset($data['user_id']) && isset($data['country'])) {
            $knownCountries = AuditLog::where('user_id', $data['user_id'])
                ->where('action', 'login')
                ->whereNotNull('country')
                ->latest()
                ->limit(5)
                ->pluck('country')
                ->toArray();

            if (! empty($knownCountries) && ! in_array($data['country'], $knownCountries, true)) {
                $reasons[] = "New country login: {$data['country']} (usual: " . implode(', ', array_unique($knownCountries)) . ")";
            }
        }

        // 3. Successful login immediately after failed logins from different IP
        if ($data['action'] === 'login' && isset($data['user_email'])) {
            $recentFailFromOtherIp = AuditLog::where('action', 'login.failed')
                ->where('user_email', $data['user_email'])
                ->where('ip_address', '!=', $data['ip_address'])
                ->where('created_at', '>=', now()->subMinutes(30))
                ->count();

            if ($recentFailFromOtherIp >= 3) {
                $reasons[] = "Credential stuffing: {$recentFailFromOtherIp} recent failures from other IPs before success";
            }
        }

        // 4. Bulk deletes: 5+ deletes in 2 minutes by same user
        if (str_contains($data['action'] ?? '', 'deleted') && isset($data['user_id'])) {
            $recentDeletes = AuditLog::where('user_id', $data['user_id'])
                ->where('action', 'like', '%.deleted')
                ->where('created_at', '>=', now()->subMinutes(2))
                ->count();

            if ($recentDeletes >= 4) {
                $reasons[] = "Bulk delete: {$recentDeletes} deletes in 2 minutes";
            }
        }

        // 5. Unauthorized access attempt (403/401 in action name)
        if (str_contains($data['action'] ?? '', 'unauthorized') || str_contains($data['action'] ?? '', 'forbidden')) {
            $reasons[] = 'Unauthorized access attempt';
        }

        // 6. Repeated unauthorized probes from same IP
        if (in_array($data['action'] ?? '', ['login.failed', 'access.denied'], true) && $data['ip_address']) {
            $recentProbes = AuditLog::where('ip_address', $data['ip_address'])
                ->whereIn('action', ['login.failed', 'access.denied'])
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($recentProbes >= 10) {
                $reasons[] = "Repeated probes: {$recentProbes} unauthorized attempts from IP in 1h";
            }
        }

        // 7. Rapid content creation (10+ creates in 1 minute — possible automated exploit)
        if (str_contains($data['action'] ?? '', '.created') && isset($data['user_id'])) {
            $recentCreates = AuditLog::where('user_id', $data['user_id'])
                ->where('action', 'like', '%.created')
                ->where('created_at', '>=', now()->subMinute())
                ->count();
            if ($recentCreates >= 10) {
                $reasons[] = "Rapid creation: {$recentCreates} creates in 1 minute";
            }
        }

        // 8. Multiple different user-agents for same session user (possible token theft)
        if (isset($data['user_id']) && isset($data['user_agent']) && $data['user_agent']) {
            $recentAgents = AuditLog::where('user_id', $data['user_id'])
                ->whereNotNull('user_agent')
                ->where('created_at', '>=', now()->subHours(2))
                ->distinct('user_agent')
                ->count('user_agent');
            if ($recentAgents >= 3) {
                $reasons[] = "Multiple user-agents: {$recentAgents} different browsers in 2h";
            }
        }

        // 9. Off-hours login (11PM–5AM UTC) — advisory signal only
        if ($data['action'] === 'login') {
            $hour = (int) now()->utc()->format('G');
            if ($hour >= 23 || $hour < 5) {
                $reasons[] = "Off-hours login: " . now()->utc()->format('H:i') . " UTC";
            }
        }

        // 10. Mass media uploads (20+ in 5 minutes — possible automated content injection)
        if (str_contains($data['action'] ?? '', 'media.') && isset($data['user_id'])) {
            $recentMedia = \App\Models\AuditLog::where('user_id', $data['user_id'])
                ->where('action', 'like', 'media.%')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();
            if ($recentMedia >= 20) {
                $reasons[] = "Mass media operation: {$recentMedia} media actions in 5 minutes";
            }
        }

        if (! empty($reasons)) {
            return [true, implode(' | ', $reasons)];
        }

        return [false, null];
    }
}
