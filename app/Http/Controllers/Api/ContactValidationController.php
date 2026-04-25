<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactValidationController extends Controller
{
    /**
     * Valida se o número está na White-list e retorna os dados do Tenant.
     * Rota: GET /api/v1/validate-contact/{phone}
     */
    public function __invoke(Request $request, string $phone): JsonResponse
    {
        // 1. Sanitização: Deixa apenas números (remove +, -, espaços, parênteses)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // 2. Busca o contato e carrega o relacionamento com a empresa (Tenant)
        $contact = Contact::where('phone_number', $cleanPhone)
            ->with('tenant')
            ->first();

        // 3. Resposta se o contato não existir
        if (!$contact) {
            return response()->json([
                'authorized' => false,
                'status' => 'not_registered',
                'message' => 'Número não encontrado em nenhuma white-list.'
            ], 404);
        }

        // 4. Resposta de sucesso (Mesmo que o status seja "blocked" ou "pending")
        // O n8n usará o campo 'authorized' para decidir o fluxo
        return response()->json([
            'authorized' => $contact->status === 'authorized',
            'status' => $contact->status,
            'contact_name' => $contact->name,
            'tenant' => [
                'id' => $contact->tenant_id,
                'name' => $contact->tenant->name ?? 'N/A',
                'slug' => $contact->tenant->slug ?? 'n-a',
            ],
            // Útil para o n8n saber se deve processar como Master ou Cliente
            'is_master_tenant' => (bool) ($contact->tenant->is_master ?? false),
        ]);
    }
}