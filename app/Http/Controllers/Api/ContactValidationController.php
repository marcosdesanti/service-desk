<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
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
         $startTime = microtime(true);   
        // 1. Sanitização: Deixa apenas números (remove +, -, espaços, parênteses)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // 2. Gera variações para lidar com o 9º dígito (Brasil: 55 + DDD + 9 + Número)
        $possibleNumbers = $this->getPhoneVariations($cleanPhone);

        // 2. Busca o contato e carrega o relacionamento com a empresa (Tenant)
        $contact = Contact::where('phone_number', $possibleNumbers)
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
       $response = response()->json([
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
        // Registrar o log antes de retornar
        ApiLog::create([
            'tenant_id' => $contact?->tenant_id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'payload_received' => $phone,
            'status_code' => $response->getStatusCode(),
            'authorized' => $contact ? ($contact->status === 'authorized') : false,
            'response_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
            'ip_address' => $request->ip(),
        ]);
        return $response;
    }

    /**
     * Gera variações de números brasileiros para ignorar erros de 9º dígito.
     */
    private function getPhoneVariations(string $phone): array
    {
        $variations = [$phone];

        // Se for um número brasileiro (DDI 55)
        if (str_starts_with($phone, '55') && strlen($phone) >= 12) {
            $ddd = substr($phone, 2, 2);
            $rest = substr($phone, 4);

            if (strlen($phone) === 13 && $rest[0] === '9') {
                // Tem 13 dígitos e começa com 9: gera versão SEM o 9
                $variations[] = '55' . $ddd . substr($rest, 1);
            } elseif (strlen($phone) === 12) {
                // Tem 12 dígitos: gera versão COM o 9
                $variations[] = '55' . $ddd . '9' . $rest;
            }
        }

        return array_unique($variations);
    }
}