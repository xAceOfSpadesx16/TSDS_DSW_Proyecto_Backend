<?php

namespace App\Modules\Legal;

/**
 * Servicio de textos legales/regulatorios.
 *
 * Los disclaimers son datos estáticos por ahora: vivir en una clase
 * permite evolucionar a persistencia DB o i18n sin tocar el controlador.
 */
class LegalService
{
    /**
     * Disclaimer principal del producto.
     *
     * Aclara que:
     *   - los sorteos son recreativos (sin valor monetario/contractual),
     *   - la aleatoriedad se calcula en el frontend,
     *   - el backend no audita justicia criptográfica de los resultados.
     *
     * @return array{title: string, version: string, sections: array<int, array{heading: string, body: string}>}
     */
    public function disclaimer(): array
    {
        return [
            'title' => 'Disclaimer - Thrive Randomizer',
            'version' => '1.0.0',
            'sections' => [
                [
                    'heading' => 'Naturaleza del servicio',
                    'body' => 'Thrive Randomizer es una herramienta recreativa para generar resultados aleatorios (sorteos, equipos, tiradas de dados). No constituye un juego de azar regulado ni un servicio de lotería; los resultados no tienen valor monetario ni efecto legal/contractual.',
                ],
                [
                    'heading' => 'Cálculo en el cliente',
                    'body' => 'La generación de resultados aleatorios se realiza íntegramente en el navegador del usuario (frontend). El backend solamente persiste el historial que el cliente decide enviar; no recalcula ni audita la aleatoriedad de los sorteos.',
                ],
                [
                    'heading' => 'Sin garantía de equidad criptográfica',
                    'body' => 'Los algoritmos utilizados (Fisher-Yates, ruleta geométrica, sorteos ponderados) son adecuados para uso recreativo. NO se garantiza que sean criptográficamente seguros ni aptos para escenarios donde la equidad matemática deba ser auditable por un tercero.',
                ],
                [
                    'heading' => 'Datos personales',
                    'body' => 'El registro solicita únicamente nombre, email y contraseña. El historial se almacena asociado al identificador del usuario y puede ser eliminado contactando al administrador.',
                ],
                [
                    'heading' => 'Limitación de responsabilidad',
                    'body' => 'El uso de la herramienta es responsabilidad exclusiva del usuario. Thrive no se hace responsable por disputas derivadas del uso recreativo de los resultados.',
                ],
            ],
        ];
    }
}