# Auditoria de Co-Autores de Evento
## Apollo Events Manager - Relatório de Análise

**Data:** 2025-01-11  
**Plugin:** `apollo-events-manager`  
**CPT:** `event_listing`  
**Status:** Análise Completa (Sem Modificações)

---

## 1. Storage Model

### Meta Key Principal
- **Meta Key:** `_event_co_authors`
- **Formato de Dados:** Array de IDs de usuários (integers)
- **Tipo:** Serialized array (WordPress padrão)

### Onde é Criado/Atualizado

#### Admin Metabox (Backend)
- **Arquivo:** `includes/admin-metaboxes.php`
- **Função:** `render_coauthors_metabox()` (linha 1400)
- **Salvamento:** `save_event_meta()` (linha 892-900)
- **Input:** Campo `<select multiple>` com todos os usuários (limitado a 500)
- **Sanitização:** `array_map( 'absint', $_POST['apollo_event_co_authors'] )`

#### Frontend Form
- **Arquivo:** `apollo-events-manager.php`
- **Linha:** 4676 (salvamento) e 4790 (leitura)
- **Input:** `$_POST['event_co_authors']` (array de IDs)
- **Sanitização:** `array_map( 'absint', $_POST['event_co_authors'] )`

### Inconsistências Críticas Identificadas

⚠️ **PROBLEMA:** Existem **duas meta keys diferentes** sendo usadas no código:

1. **`_event_co_authors`** (CORRETO - usado em admin-metaboxes.php e apollo-events-manager.php)
   - Usado em: admin-metaboxes.php, apollo-events-manager.php, event-data-helper.php

2. **`_apollo_coauthors`** (INCORRETO - usado em fallbacks)
   - Usado em: shortcodes-my-apollo.php (linha 91), class-apollo-events-analytics.php (linha 117), shortcode-user-dashboard.php (linha 49), admin-metakeys-page.php (linha 331)

**Impacto:** Queries que buscam por `_apollo_coauthors` não encontrarão eventos com co-autores salvos em `_event_co_authors`.

### Validação/Sanitização
- ✅ IDs são sanitizados com `absint()` antes de salvar
- ✅ Arrays vazios são salvos como `array()` (não `null`)
- ✅ Verificação de tipo: `is_array( $co_authors )` antes de processar

---

## 2. Co-Authors Plus Integration

### Status: **PARCIALMENTE IMPLEMENTADO**

### Configuração
- **Arquivo:** `apollo-events-manager.php`
- **Função:** `configure_coauthors_support()` (linha 3279)
- **Hook:** `init` (prioridade 20)

### Verificações de Dependência
```php
// Verifica se Co-Authors Plus está ativo
if ( ! function_exists( 'coauthors_support_theme' ) ) {
    // Log warning apenas em debug mode
    return;
}
```

### Post Types Suportados
- ✅ `event_listing` - adicionado via `add_post_type_support( 'event_listing', 'co-authors' )`
- ✅ `event_dj` - adicionado via `add_post_type_support( 'event_dj', 'co-authors' )`
- ✅ Registrado no filter `coauthors_supported_post_types`

### Uso da API Co-Authors Plus

#### Onde é Usado:
1. **`includes/shortcodes-my-apollo.php`** (linha 63-81)
   - Usa `get_coauthors( $event->ID )` para buscar eventos co-autorados
   - **Fallback:** Se não existir, usa `_apollo_coauthors` (meta key incorreta)

2. **`templates/shortcode-user-dashboard.php`** (linha 42-56)
   - Verifica `function_exists( 'get_coauthors' )`
   - **Problema:** Usa `_apollo_coauthors` no fallback (deveria ser `_event_co_authors`)

### Problemas Identificados

1. **Integração Dupla:**
   - O plugin tenta usar Co-Authors Plus quando disponível
   - Mas também mantém sua própria meta key `_event_co_authors`
   - **Risco:** Dados podem ficar dessincronizados se Co-Authors Plus for desativado

2. **Fallback Incorreto:**
   - Quando Co-Authors Plus não está disponível, o código busca `_apollo_coauthors`
   - Mas os dados são salvos em `_event_co_authors`
   - **Resultado:** Fallback nunca encontra dados

3. **Sem Sincronização:**
   - Não há código que sincronize `_event_co_authors` com Co-Authors Plus
   - Se um evento é editado via Co-Authors Plus UI, `_event_co_authors` não é atualizado

---

## 3. Templates & REST

### Templates

#### ❌ **Nenhum template exibe co-autores**

Arquivos verificados:
- `templates/event-card.php` - **Não exibe co-autores**
- `templates/single-event-standalone.php` - **Não exibe co-autores**
- `templates/shortcode-user-dashboard.php` - Apenas conta eventos co-autorados, não exibe lista

**Conclusão:** Co-autores não são renderizados em nenhum template público.

### REST API

#### ❌ **Co-autores não estão no payload REST**

**Arquivo:** `includes/class-rest-api.php`
**Função:** `format_event()` (linha 251-350)

**Análise:**
- ✅ Retorna `author` (autor principal) com ID, nome e avatar
- ❌ **Não retorna `co_authors` ou `coauthors`**
- ❌ Não verifica `_event_co_authors` meta
- ❌ Não usa `get_coauthors()` se Co-Authors Plus estiver ativo

**Endpoints afetados:**
- `GET /apollo/v1/eventos` - Lista de eventos
- `GET /apollo/v1/evento/{id}` - Evento individual
- `GET /apollo/v1/meus-eventos` - Eventos do usuário

**Impacto:** Frontend/SPA não tem acesso a dados de co-autores via REST.

---

## 4. Status & Issues

### Classificação: **PARCIALMENTE IMPLEMENTADO (COM BUGS)**

### Funcionalidades Funcionais ✅
1. **Admin UI:** Metabox funcional para selecionar co-autores
2. **Salvamento:** Dados são salvos corretamente em `_event_co_authors`
3. **Permissões:** Co-autores podem editar eventos (via filter `user_has_cap` em event-data-helper.php)
4. **Integração Co-Authors Plus:** Configuração básica funciona quando plugin está ativo

### Problemas Críticos ❌

1. **Meta Key Inconsistente**
   - Código usa `_event_co_authors` para salvar
   - Mas busca `_apollo_coauthors` em fallbacks
   - **Resultado:** Queries de fallback nunca encontram dados

2. **Co-autores Não Exibidos**
   - Templates não renderizam co-autores
   - REST API não retorna co-autores
   - **Resultado:** Funcionalidade "invisível" para usuários

3. **Dessincronização com Co-Authors Plus**
   - Se Co-Authors Plus for usado, `_event_co_authors` não é atualizado
   - Se Co-Authors Plus for desativado, dados podem ser perdidos

4. **Falta de Validação**
   - Não verifica se usuários selecionados existem antes de salvar
   - Não valida se usuário tem permissão para ser co-autor

### Bugs Menores ⚠️

1. **Limite de Usuários:** Metabox limita a 500 usuários (pode ser problema em sites grandes)
2. **Sem Paginação:** Lista de usuários no select não tem paginação
3. **Sem Busca:** Não há campo de busca no metabox de co-autores
4. **Sem Logs:** Não há logs quando co-autores são adicionados/removidos

---

## 5. Recommended Next Phases

### Fase 1: Correções Críticas (PRIORIDADE ALTA)

1. **Unificar Meta Key**
   - Decidir se usa `_event_co_authors` ou `_apollo_coauthors`
   - Atualizar todos os arquivos para usar a mesma key
   - Criar script de migração para eventos existentes (se necessário)

2. **Corrigir Fallbacks**
   - Atualizar `shortcodes-my-apollo.php` para usar `_event_co_authors`
   - Atualizar `class-apollo-events-analytics.php` para usar `_event_co_authors`
   - Atualizar `shortcode-user-dashboard.php` para usar `_event_co_authors`

3. **Adicionar Co-autores ao REST API**
   - Modificar `format_event()` em `class-rest-api.php`
   - Adicionar campo `co_authors` no payload
   - Expandir dados (IDs, nomes, avatares) similar ao campo `author`
   - Verificar Co-Authors Plus e usar `get_coauthors()` se disponível

### Fase 2: Exibição em Templates (PRIORIDADE MÉDIA)

4. **Adicionar Co-autores aos Templates**
   - `event-card.php`: Exibir avatares/links de co-autores (opcional, não crítico)
   - `single-event-standalone.php`: Adicionar seção "Co-autores" ou "Organizadores"
   - Considerar usar componente reutilizável para consistência

5. **Melhorar Admin UI**
   - Adicionar campo de busca no metabox
   - Implementar paginação ou lazy loading
   - Adicionar validação de permissões

### Fase 3: Sincronização e Robustez (PRIORIDADE BAIXA)

6. **Sincronizar com Co-Authors Plus**
   - Criar hook que sincroniza `_event_co_authors` quando Co-Authors Plus atualiza
   - Adicionar action `coauthors_plus_post_save` para manter dados em sync
   - Documentar comportamento quando ambos sistemas estão ativos

7. **Validação e Logs**
   - Validar existência de usuários antes de salvar
   - Adicionar logs quando co-autores são modificados
   - Criar função de validação de permissões

8. **Testes**
   - Criar testes unitários para salvamento/leitura de co-autores
   - Testar integração com Co-Authors Plus (quando ativo)
   - Testar fallback quando Co-Authors Plus está inativo
   - Testar permissões de edição para co-autores

---

## Resumo Executivo

**Status Atual:** Funcionalidade parcialmente implementada com bugs críticos que impedem uso efetivo.

**Principais Problemas:**
1. Meta key inconsistente (`_event_co_authors` vs `_apollo_coauthors`)
2. Co-autores não aparecem em templates ou REST API
3. Dessincronização potencial com Co-Authors Plus

**Recomendação:** Corrigir bugs críticos (Fase 1) antes de adicionar novas funcionalidades. A funcionalidade está "quase pronta" mas não é utilizável no estado atual.

**Estimativa de Esforço:**
- Fase 1 (Crítico): 4-6 horas
- Fase 2 (Templates): 3-4 horas  
- Fase 3 (Robustez): 4-6 horas
- **Total:** 11-16 horas para implementação completa

---

**Fim do Relatório**




