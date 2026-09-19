# Auditoria Casa Kotti Avaliações (1.3.0)

## Problemas encontrados (antes desta revisão)

- Página/pergunta já era 1:N, mas faltavam tipos (telefone, data, consentimento, hidden, checkbox).
- Editor sem largura, valor padrão, ajuda e mensagem de erro própria.
- Montserrat dependia do tema; pesos 400/500/600 não estavam explícitos.
- Admin sem exclusão de página, confirmação incompleta e formulários aninhados.
- Sem preview do wizard completo no admin.
- Respostas sem `field_type` / `schema_version`.
- Condicionais só no modo “mostrar quando”.
- Sem drag-and-drop de ordem.

## O que foi preservado

REST, nonce, honeypot, rate limit, slugs de sistema, dashboard, CSV, QR, validação compartilhada, páginas com várias perguntas, Quase lá.
