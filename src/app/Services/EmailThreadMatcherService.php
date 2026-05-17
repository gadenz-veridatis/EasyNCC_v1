<?php

namespace App\Services;

use App\Models\ThreadEmail;
use Illuminate\Support\Facades\Log;

class EmailThreadMatcherService
{
    /**
     * Try to find an existing richiesta_id for an incoming email message.
     *
     * Returns the richiesta_id if matched, null otherwise.
     */
    public function match(int $companyId, int $mailboxId, string $threadIdGmail, ?string $inReplyTo, ?string $references): ?string
    {
        // 1. Match by Gmail thread ID in the same mailbox
        $existing = ThreadEmail::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('mailbox_id', $mailboxId)
            ->where('thread_id_gmail', $threadIdGmail)
            ->whereNotNull('richiesta_id')
            ->first();

        if ($existing) {
            Log::channel('gmail')->info("Thread match by gmail thread_id: {$threadIdGmail}");
            return $existing->richiesta_id;
        }

        // 2. Match by In-Reply-To header
        if ($inReplyTo) {
            $existing = ThreadEmail::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('message_id_rfc', $inReplyTo)
                ->whereNotNull('richiesta_id')
                ->first();

            if ($existing) {
                Log::channel('gmail')->info("Thread match by In-Reply-To: {$inReplyTo}");
                return $existing->richiesta_id;
            }
        }

        // 3. Match by any element in the References chain
        if ($references) {
            $refIds = preg_split('/\s+/', trim($references));
            foreach ($refIds as $refId) {
                $refId = trim($refId);
                if (empty($refId)) continue;

                $existing = ThreadEmail::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('message_id_rfc', $refId)
                    ->whereNotNull('richiesta_id')
                    ->first();

                if ($existing) {
                    Log::channel('gmail')->info("Thread match by References: {$refId}");
                    return $existing->richiesta_id;
                }
            }
        }

        // 4. No match found
        return null;
    }
}
