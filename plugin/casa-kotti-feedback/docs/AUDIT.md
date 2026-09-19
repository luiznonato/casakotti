# Auditoria Casa Kotti Avaliações (1.4.0)

## 1.4.0

- Layout sem cartões: alternativas e escala em linhas, fieldset sem `min-inline-size` que recortava texto, `overflow` visível.
- Motor condicional: `show`, `hide`, `goto` (página) e `end`, no admin, no JS e no REST.
- Vários questionários (`casa_kotti_feedback_surveys` + `survey_id`), shortcode `[casa_kotti_feedback slug="…"]`.
- Preview admin não envia para a API. REST recusa `preview`, limita payload, aceita só slugs do questionário ativo e valida a rota no servidor.

## Preservado

REST `/casa-kotti/v1/feedback`, nonce, honeypot, rate limit, slugs de sistema, dashboard, CSV, QR, validação compartilhada, páginas com várias perguntas, Quase lá.
