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
