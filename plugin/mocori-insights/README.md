# Mocori Insights

Plugin WordPress proprietário da Mocori para analytics de visitantes, sessões, aquisição, eventos, conversões e dashboards.

A Casa Kotti (ou qualquer outro site) é apenas uma instalação. O core não contém nomes, cores, formulários ou conversões de clientes.

## Instalar

1. Envie `dist/mocori-insights.zip` em **Plugins > Adicionar plugin**.
2. Ative **Mocori Insights**.
3. Em **Mocori Insights > Configurações > Projeto**, defina nome, domínio, timezone, moeda e retenção.
4. Defina conversões (evento + form id + nome).
5. O tracker começa a coletar `page_view` e, em formulários com `data-mocori-insights-form`, `form_view` / `form_start` / `form_submit`.

## JavaScript

```js
MocoriInsights.track('form_submit', { form_id: 'contact-form' });
MocoriInsights.convert('form_submit', { form_id: 'contact-form', conversion_name: 'Solicitação de contato' });
MocoriInsights.identify({ visitor_id, session_id, lead_id: '123', source: 'custom_form' });
MocoriInsights.consent(true);
MocoriInsights.consent(false);
```

Marcar um formulário para auto-tracking:

```html
<form data-mocori-insights-form="contact-form" data-mocori-insights-form-name="Contato">
```

## PHP

```php
mocori_insights()->track( 'form_submit', array(
  'visitor_id' => $uuid,
  'session_id' => $session,
  'metadata'   => array( 'form_id' => 'contact-form' ),
) );

mocori_insights()->identify( array(
  'visitor_id' => $uuid,
  'session_id' => $session,
  'lead_id'    => (string) $lead_id, // opaco; sem e-mail
  'source'     => 'custom_form',
) );
```

Hooks: `mocori_insights_event_recorded`, `mocori_insights_conversion_recorded`, `mocori_insights_session_started`, `mocori_insights_visitor_created`, `mocori_insights_seed_conversions`, `mocori_insights_register_integrations`.

## Privacidade

- Identificadores aleatórios (`visitor_uuid`, `session_uuid`).
- Sem fingerprinting.
- IP não é armazenado (hash efêmero só para rate limit).
- Metadata bloqueia e-mail, telefone e similares.
- Consentimento configurável; API para CMP.
- Retenção via WP-Cron; agregados diários permanecem.

## Licença / Cloud

`license_key`, `installation_id`, `site_uuid` e `plugin_version` existem na configuração. A v1.0.0 funciona sem validação externa. Não há envio a `insights.mocori.com.br` nesta versão.

## Pacote

```bash
(cd plugin && zip -qr ../dist/mocori-insights.zip mocori-insights)
```
