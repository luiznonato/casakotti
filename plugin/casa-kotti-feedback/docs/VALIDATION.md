# Validação do questionário (1.2.0)

Frontend (`public/ckf-validate.js`) e backend (`CKF_Validate`) compartilham as mesmas regras.

## Páginas

Progresso conta **páginas aplicáveis**, não perguntas. Nome, e-mail e consentimento ficam na página `identificacao` (“Quase lá”).

## Testes Node

```
node plugin/casa-kotti-feedback/tests/validate.test.js
```

Cobertura: text (vazio/espaços/máximo), textarea, e-mail (vazio/válido/inválido), single_choice, radio, multi_choice (min/max), checkbox opcional/obrigatório, stars, scale, info, select placeholder, página de contato opcional.

## Preview local

`plugin/casa-kotti-feedback/tests/preview.html` — wizard com 4 páginas (produto, fragrância, avaliação com 2 campos, identificação com 3 campos).

## Preview no Chrome (headless)

`getComputedStyle` em pergunta, alternativa, Nome, E-mail, placeholder, consentimento, Próximo, Enviar, erro, NPS, estrela e privacidade: `"Montserrat CK", Montserrat, sans-serif`.

Wizard (`tests/wizard-run.html`):

- progresso `01 / 04` (páginas)
- Próximo sem resposta: erro inline, não avança
- página Avaliação com estrelas + NPS: dois erros ao mesmo tempo
- Quase lá: Nome + E-mail + aceite juntos
- e-mail `maria@`: `Digite um e-mail válido.`
- Voltar preserva estrelas
