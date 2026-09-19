# Casa Kotti — Avaliações

Plugin WordPress próprio para o questionário de experiência do cliente. Não depende de plugins de formulário de terceiros.

## Instalar e ativar

1. Envie `dist/casa-kotti-feedback.zip` em **Plugins > Adicionar plugin > Enviar plugin**.
2. Ative **Casa Kotti — Avaliações**.
3. Na ativação/atualização o plugin cria (via `dbDelta`) as tabelas de respostas, fragrâncias, **páginas**, **perguntas**, **alternativas** e **respostas dinâmicas**. A versão de schema é `1.2.0` (`ckf_db_version`). A migração `ckf_steps_migrated` agrupa Nome, E-mail e aceite na página **Quase lá**.
4. Se a tabela de perguntas estiver vazia, o fluxo atual é semeado **uma única vez** (`ckf_questions_seeded`). Reativar o plugin não duplica perguntas.

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

## Question Builder

Em **Casa Kotti > Questionário** é possível criar páginas e, dentro delas, criar, editar, mover, reordenar, ativar/desativar e (quando seguro) excluir perguntas. Uma página pode ter várias perguntas. O progresso do cliente conta páginas.

Tipos: texto, textarea, seleção única, radio, multiseleção, select, estrelas, escala, sim/não, e-mail, número, informativo.

Perguntas de sistema (`product`, `fragrance`, `overall_rating`, `nps_score`, etc.) têm slug protegido. A fragrância continua vindo de **Casa Kotti > Fragrâncias**, não de alternativas estáticas.

Intro e tela final: **Casa Kotti > Configurações**.

Condicionais: uma regra simples na UI (é igual a, contém, maior que…). O banco já aceita grupos AND/OR aninhados (o fluxo de refil/difusor usa OR).

## Cadastro de fragrâncias

Em **Casa Kotti > Fragrâncias**:

- Nome, slug, status (Ativa / Inativa) e ordem
- Criar, editar, ativar/desativar e reordenar
- Só fragrâncias **ativas** aparecem no questionário
- Desativar não apaga avaliações antigas (o nome da fragrância fica gravado na resposta)

Cadastre ao menos uma fragrância ativa antes de divulgar o QR Code.

## Consultar avaliações

**Casa Kotti > Avaliações** permite **excluir** cada resposta (lista e detalhe), com confirmação. A exclusão remove a avaliação e as respostas dinâmicas associadas.

**Casa Kotti > Avaliações** mostra:

- Cards: total, nota média, NPS, % de recompra (Com certeza + Provavelmente sim)
- Métricas por Home Spray, Difusor, Automotivo e Refil
- Listagem com data, produto, fragrância, nota, intensidade, NPS, recompra e cliente
- Filtros: produto, fragrância, nota, faixa NPS, período, busca por nome/e-mail
- Clique na data para o detalhe completo

NPS = % promotores (9–10) − % detratores (0–6). Neutros são 7 e 8.

## Exportar CSV

O botão **Exportar CSV** respeita os filtros. Colunas fixas do dashboard + colunas dinâmicas para perguntas personalizadas (multiselect unido por `; `). UTF-8 com BOM. Células `= + - @` são prefixadas.

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

`wp_casa_kotti_feedback_steps`: páginas do wizard (`slug`, título, descrição, ordem, status).

`wp_casa_kotti_feedback_questions`: slug, título, tipo, obrigatoriedade, status, ordem, `step_id`, `is_system`, `settings_json`.

`wp_casa_kotti_feedback_question_options`: value estável + label editável.

`wp_casa_kotti_feedback_answers`: uma linha por valor (`multi_choice` = N linhas).

Perguntas de sistema também atualizam as colunas da tabela principal, para o dashboard continuar igual.

IP puro nunca é persistido.

Cobre oficial do questionário: `#b57a54` (`--ck-copper`), usado só em interação. Tipografia: Montserrat Regular (`"Montserrat CK"`, peso 400; botões 500).

## Envio

`POST /wp-json/casa-kotti/v1/feedback`

```json
{ "answers": { "product": "home-spray", "overall_rating": 5 }, "source": "", "batch": "" }
```

Campos soltos no root ainda são aceitos (retrocompatibilidade). Nonce REST, honeypot, rate limit e validação contra a definição atual. Respostas de perguntas que não se aplicam à condição são ignoradas. Consentimento de marketing não é obrigatório.

## Testes manuais sugeridos

- Atualizar plugin com respostas antigas: seed uma vez, dashboard intacto.
- Builder: criar/editar/duplicar/reordenar/desativar; alterar só o label de uma opção.
- Cada tipo de campo no wizard público; progresso muda com condicionais.
- Voltar/avançar, erro inline, submit com servidor recusando (estado preservado).
- QR `?produto=spray` pré-seleciona `home-spray`.
- Mobile 320–430px: estrelas, escala, cards sem scroll horizontal.

Hook reservado para o futuro (CRM, cupom, e-mail): `casa_kotti_feedback_saved`.

## Gerar o ZIP

```bash
(cd plugin && zip -qr ../dist/casa-kotti-feedback.zip casa-kotti-feedback)
```
