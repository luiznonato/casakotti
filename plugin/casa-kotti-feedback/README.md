# Casa Kotti — Avaliações

Plugin WordPress próprio para o questionário de experiência do cliente. Não depende de plugins de formulário de terceiros.

## Instalar e ativar

1. Envie `dist/casa-kotti-feedback.zip` em **Plugins > Adicionar plugin > Enviar plugin**.
2. Ative **Casa Kotti — Avaliações**.
3. Na ativação o plugin cria (via `dbDelta`) as tabelas `wp_casa_kotti_feedback` e `wp_casa_kotti_fragrances`.

O plugin pode ser desativado normalmente. As tabelas e as respostas permanecem no banco.

## Página pública

1. Crie uma página WordPress, por exemplo **Experiência**, com slug `experiencia` (URL `/experiencia/`).
2. No conteúdo, insira o shortcode:

```
[casa_kotti_feedback]
```

O shortcode funciona no editor de blocos e no clássico. Não usa iframe.

CSS e JavaScript do questionário são carregados **somente** nessa página.

Com o tema Casa Kotti, a página reutiliza o fundo grafite, Montserrat, tokens `--ck-*` e as folhagens laterais já existentes. A homepage não é alterada pelo plugin.

## Cadastro de fragrâncias

Em **Casa Kotti > Fragrâncias**:

- Nome, slug, status (Ativa / Inativa) e ordem
- Criar, editar, ativar/desativar e reordenar
- Só fragrâncias **ativas** aparecem no questionário
- Desativar não apaga avaliações antigas (o nome da fragrância fica gravado na resposta)

Cadastre ao menos uma fragrância ativa antes de divulgar o QR Code.

## Consultar avaliações

**Casa Kotti > Avaliações** mostra:

- Cards: total, nota média, NPS, % de recompra (Com certeza + Provavelmente sim)
- Métricas por Home Spray, Difusor, Automotivo e Refil
- Listagem com data, produto, fragrância, nota, intensidade, NPS, recompra e cliente
- Filtros: produto, fragrância, nota, faixa NPS, período, busca por nome/e-mail
- Clique na data para o detalhe completo

NPS = % promotores (9–10) − % detratores (0–6). Neutros são 7 e 8.

## Exportar CSV

O botão **Exportar CSV** respeita os filtros aplicados. O arquivo é UTF-8 com BOM para o Excel. Células que começam com `= + - @` são prefixadas para evitar fórmulas.

## Parâmetros de URL (QR Code)

A página aceita query strings sanitizadas. Nada da URL é gravado sem validação.

| Parâmetro | Uso |
|---|---|
| `produto` | Pré-seleciona o produto (`home-spray`, `difusor`, `automotivo`, `refil`, `mais-de-um`). A primeira pergunta pode ser omitida; o cliente pode **Alterar produto**. |
| `fragrancia` | Slug de uma fragrância cadastrada. |
| `lote` | Gravado em `batch`. |
| `origem` | Gravado em `source`. |
| `campanha` | Gravado em `campaign`. |

Exemplos:

- `/experiencia/`
- `/experiencia/?produto=difusor`
- `/experiencia/?produto=difusor&fragrancia=xyz&lote=240926`

## Banco

`wp_casa_kotti_feedback`: uuid, produto, fragrância (nome + slug), notas, intensidade, resposta específica do produto, performance, recompra, NPS, comentários, nome, e-mail, consentimento de marketing (separado e opcional), origem, campanha, lote, data UTC, hash irreversível de IP, user agent.

`wp_casa_kotti_fragrances`: nome, slug, status, ordem.

IP puro nunca é persistido.

## Envio

`POST /wp-json/casa-kotti/v1/feedback` com nonce REST, honeypot, rate limit e validação no servidor. O consentimento de marketing não é obrigatório.

Hook reservado para o futuro (CRM, cupom, e-mail): `casa_kotti_feedback_saved`.

## Gerar o ZIP

```bash
(cd plugin && zip -qr ../dist/casa-kotti-feedback.zip casa-kotti-feedback)
```
