<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add type column to quote_email_templates
        Schema::table('quote_email_templates', function (Blueprint $table) {
            $table->string('type', 50)->default('quote')->after('company_id');
            $table->index(['company_id', 'type']);
        });

        // Create default service_assignment and service_closure templates for each company
        $companies = DB::table('companies')->pluck('id');

        foreach ($companies as $companyId) {
            DB::table('quote_email_templates')->insert([
                [
                    'company_id' => $companyId,
                    'type' => 'service_assignment',
                    'name' => 'Assegnazione Servizio (Default)',
                    'subject' => 'Assegnazione servizio {{reference_number}} del {{service_date}}',
                    'body_html' => $this->getAssignmentTemplateHtml(),
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'company_id' => $companyId,
                    'type' => 'service_closure',
                    'name' => 'Chiusura Servizio (Default)',
                    'subject' => 'Chiusura servizio {{reference_number}} del {{service_date}}',
                    'body_html' => $this->getClosureTemplateHtml(),
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        // Remove service templates
        DB::table('quote_email_templates')
            ->whereIn('type', ['service_assignment', 'service_closure'])
            ->delete();

        Schema::table('quote_email_templates', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'type']);
            $table->dropColumn('type');
        });
    }

    private function getAssignmentTemplateHtml(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333;">Nuovo Servizio Assegnato</h2>
    <p>Gentile Collega,</p>
    <p>ti è stato assegnato il seguente servizio:</p>
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5; font-weight: bold;">Riferimento</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{reference_number}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5; font-weight: bold;">Data</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{service_date}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5; font-weight: bold;">Pickup</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{pickup_time}} - {{pickup_address}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5; font-weight: bold;">Dropoff</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{dropoff_time}} - {{dropoff_address}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5; font-weight: bold;">Passeggero</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{passenger_name}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5; font-weight: bold;">N. Passeggeri</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{passenger_count}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5; font-weight: bold;">Veicolo</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{vehicle_info}}</td>
        </tr>
    </table>
    <p>In allegato trovi il foglio di servizio con tutti i dettagli.</p>
    <p style="text-align: center; margin: 30px 0;">
        {{accept_link}}
    </p>
    <p>Cordiali saluti,<br>{{company_name}}</p>
</div>
HTML;
    }

    private function getClosureTemplateHtml(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333;">Chiusura Servizio</h2>
    <p>Gentile Collega,</p>
    <p>il servizio <strong>{{reference_number}}</strong> del <strong>{{service_date}}</strong> è stato accettato.</p>
    <p>Al termine del servizio, conferma la chiusura cliccando sul pulsante qui sotto:</p>
    <p style="text-align: center; margin: 30px 0;">
        {{closure_link}}
    </p>
    <p>Cordiali saluti,<br>{{company_name}}</p>
</div>
HTML;
    }
};
