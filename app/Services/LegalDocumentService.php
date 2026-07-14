<?php

namespace App\Services;

use App\Models\LegalDocument;
use App\Models\UserLegalAgreement;
use Illuminate\Support\Facades\DB;

class LegalDocumentService
{
    public function latestDocuments(): array
    {
        $docs = LegalDocument::query()
            ->where('is_active', true)
            ->whereIn('document_type', [LegalDocument::TYPE_TERMS, LegalDocument::TYPE_PRIVACY])
            ->orderByDesc('published_at')
            ->get()
            ->unique('document_type')
            ->values();

        return $this->ok('Latest legal documents', [
            'documents' => $docs->map(fn (LegalDocument $doc) => [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'version_number' => $doc->version_number,
                'content_text' => $doc->content_text,
                'published_at' => optional($doc->published_at)?->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * Record acceptance for one or more legal document IDs for a user.
     */
    public function accept(int $userId, array $documentIds, ?string $ipAddress): array
    {
        $documents = LegalDocument::whereIn('id', $documentIds)
            ->where('is_active', true)
            ->get();

        if ($documents->count() !== count(array_unique($documentIds))) {
            return $this->error('One or more legal documents are invalid', 422);
        }

        DB::transaction(function () use ($documents, $userId, $ipAddress) {
            foreach ($documents as $document) {
                UserLegalAgreement::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'legal_document_id' => $document->id,
                    ],
                    [
                        'accepted_at' => now(),
                        'ip_address' => $ipAddress,
                    ]
                );
            }
        });

        return $this->ok('Legal agreements recorded', [
            'accepted_document_ids' => $documents->pluck('id')->values()->all(),
            'is_compliant' => $this->userHasAcceptedCurrent($userId),
        ], 201);
    }

    public function userHasAcceptedCurrent(int $userId): bool
    {
        foreach ([LegalDocument::TYPE_TERMS, LegalDocument::TYPE_PRIVACY] as $type) {
            $latest = LegalDocument::where('document_type', $type)
                ->where('is_active', true)
                ->orderByDesc('published_at')
                ->first();

            if (!$latest) {
                continue;
            }

            $accepted = UserLegalAgreement::where('user_id', $userId)
                ->where('legal_document_id', $latest->id)
                ->exists();

            if (!$accepted) {
                return false;
            }
        }

        return true;
    }

    private function ok(string $message, array $data = [], int $code = 200): array
    {
        return [
            'http_code' => $code,
            'body' => [
                'status' => 'success',
                'message' => $message,
                'data' => $data,
                'errors' => null,
            ],
        ];
    }

    private function error(string $message, int $code): array
    {
        return [
            'http_code' => $code,
            'body' => [
                'status' => 'error',
                'message' => $message,
                'data' => new \stdClass(),
                'errors' => ['error' => [$message]],
            ],
        ];
    }
}
