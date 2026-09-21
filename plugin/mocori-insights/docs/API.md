# Mocori Insights — APIs internas

O core não conhece clientes. Extensões falam com estas APIs.

## JavaScript (`window.MocoriInsights`)

| Método | Uso |
|---|---|
| `track(name, metadata)` | Evento genérico |
| `identify({ lead_id, visitor_id, session_id, source })` | Relaciona visitante a um lead opaco |
| `convert(name, metadata)` | Evento `conversion` |
| `consent(true\|false)` / `setConsent()` | LGPD / CMP |

Fila antes do load: `window.MocoriInsights = window.MocoriInsights \|\| []; window.MocoriInsights.push(['track', 'video_play', { id: 'x' }]);`

Auto-tracking: `form[data-mocori-insights-form]`. `data-mocori-insights-skip-submit="1"` deixa o submit por conta da integração (sucesso no servidor).

## PHP

```php
mocori_insights()->track( $event, $payload );
mocori_insights()->identify( array( 'visitor_id' => $uuid, 'session_id' => $sid, 'lead_id' => '42', 'source' => 'custom_form' ) );
mocori_insights()->convert( 'form_submit', array( 'metadata' => array( 'form_id' => 'contact-form' ) ) );
mocori_insights_track( 'page_view', array() );
```

REST público: `POST /wp-json/mocori-insights/v1/collect` com header `X-Mocori-Insights-Token`.

REST autenticado: `GET /wp-json/mocori-insights/v1/realtime` (`view_mocori_insights`).

## Hooks

- `mocori_insights_loaded`
- `mocori_insights_activated`
- `mocori_insights_event_recorded`
- `mocori_insights_conversion_recorded`
- `mocori_insights_session_started`
- `mocori_insights_visitor_created`
- `mocori_insights_visitor_identified`
- `mocori_insights_seed_conversions`
- `mocori_insights_register_integrations`
- `mocori_insights_default_settings`
- `mocori_insights_consent_allows`
- `mocori_insights_allowed_events`
- `mocori_insights_blocked_metadata_keys`

## Integrações futuras

WooCommerce, Elementor, CF7, WPForms, Gravity, Fluent, HubSpot, Salesforce e Mocori CRM devem registrar-se em `mocori_insights_register_integrations`. Insights permanece standalone.
