# BloomGift AI Sales Advisor — progress audit

Last checked: 2026-10-10

| Check | Result | Evidence / limitation |
|---|---|---|
| AI UX quality | PASS | Compact floating chat; a page reload starts a clean chat; current-turn state stays in the page. Browser checked at `http://127.0.0.1:8000/`. |
| Advice depth | PARTIAL | Local fallback now gives specific, detailed custom concepts. Online model is not active because the local environment has no provider key/model configured; product descriptions in the catalogue can also be sparse. |
| Custom detail | PASS | Custom suggestions include composition, palette, form, wrapping, mood, occasion/recipient fit, reason, customization, and price confirmation wording without a made-up price or stock. |
| Generic name filter | PASS | Online output rejects known generic names and incomplete/short custom descriptions. |
| Quality gate | PARTIAL | Required fields, minimum descriptions, duplicate compositions, recent names, catalogue grounding, budget, color, exclusions, and type filters are checked. There is no independent semantic evaluator for every relevance/diversity judgment. |
| Selected concept memory | PASS | Selected concept and its full design description remain in the current chat; reload clears them. |
| Design iteration | PASS | Browser and feature tests verified changing a selected flower concept and adding chocolate; long descriptions no longer fail request validation. |
| Recommendation history | PASS | Current chat sends recent product IDs and concept names; rejected selected concepts are replaced. State resets on page reload. |
| Smart autoscroll | PASS | Follow-latest behavior stops when the customer scrolls up and offers “Tin nhắn mới”. |
| Progressive card | PASS | Cards start with short copy; detail fields are behind “Xem chi tiết”. |
| Real online AI | PASS | `.env` selects OpenRouter Free (`openrouter/free`); the live browser request reached OpenRouter and returned valid structured JSON without fallback. The server-side API key is not written to logs or source. |
| Real web search | NO | Server-side Brave adapter and source handling are implemented, but `.env` has no Brave API key. Live internet search was not claimed or tested. |
| Force web search | PASS (automated) | “Tìm thêm trên mạng” triggers the Brave search path without a second model decision when configured; mocked HTTP feature test passed. Real search remains unavailable until credentials are supplied. |
| Product grounding | PASS | Product IDs, price, sale price, stock, images, and URLs are taken from active BloomGift products; contradictory color/style candidates are filtered. |
| Custom order prefill | PASS | Full brief is prefilled; budget is omitted when cleared; no request is submitted by the advisor. |
| Gradual answer rendering | PASS (automated + browser) | OpenRouter streamed the live answer via SSE; the browser displayed the validated answer and catalogue cards. Non-blocking reads enforce the configured stream timeout. |
| Database changes | NONE | No migration, seed, schema, product, order, payment, finance, or tax data was changed. Regression tests used an in-memory SQLite database. |
| Full regression | PASS | 136 tests, 1,036 assertions. Checkout, COD, PayPal, voucher, order, finance, and other existing feature tests passed. |
| Browser verification | PASS | Asked “Gợi ý quà sinh nhật cho người yêu khoảng 300.000đ”. Runtime log confirmed OpenRouter HTTP 200, response received, structured output valid, and fallback unused. Browser showed Chocolate Sweet Moments (229.000₫) and Nến thơm Amber Glow (249.000₫), both linked to BloomGift catalogue records. |

## Configuration needed to activate online services

OpenRouter Free is configured in `.env` for this demo (keep the API key server-side and do not commit `.env`):

```dotenv
BLOOMGIFT_AI_PROVIDER=openrouter
BLOOMGIFT_AI_API_KEY=
BLOOMGIFT_AI_BASE_URL=https://openrouter.ai/api/v1
BLOOMGIFT_AI_MODEL=openrouter/free
BLOOMGIFT_AI_TIMEOUT=20
BLOOMGIFT_AI_JSON_MODE=true
BLOOMGIFT_AI_STREAM_TIMEOUT=45
BLOOMGIFT_WEB_SEARCH_PROVIDER=brave
BLOOMGIFT_WEB_SEARCH_API_KEY=
BLOOMGIFT_WEB_SEARCH_BASE_URL=https://api.search.brave.com/res/v1/web/search
BLOOMGIFT_WEB_SEARCH_TIMEOUT=5
```

The optional Brave web-search credential is still not configured; the Advisor reports that honestly and does not label built-in knowledge as a web result.

## Latest relevance and intent-lock upgrade — 2026-10-10

| Check | Result | Evidence / limitation |
|---|---|---|
| Exact product-type lock | PASS | “shop có loại gấu bông khác không” returns only in-stock teddy products; unrelated gifts and custom designs are excluded. |
| Ask before changing type | PASS | When no teddy alternative remains, the Advisor states that directly and asks whether the customer wants custom teddy or a different gift type. |
| Customer-authorized expansion | PASS | A new “quà khác đi” request clears the prior teddy lock and opens gift recommendations. |
| Inventory-only preference | PASS | “chỉ hàng có sẵn” excludes made-to-order/custom cards. |
| Permission-based custom alternatives | PASS | A simple exact-entity search with no matching stock asks first; custom designs remain available when the customer explicitly asks for them or supplies a detailed design brief. |
| Negative preference handling | PASS | Excluded entities such as “đừng đưa chocolate” do not become positive required-item filters. |
| Catalog note sanitization | PASS | Internal AI/demo/image notes are removed from customer-facing product descriptions while safe catalog text remains. |
| Non-generic catalog reasons | PASS | When descriptions are sparse, reasons use the product's actual name/type and relevant budget context (for example, a greeting card for a birthday), instead of a repeated missing-description message. |
| Live browser exact-type check | PASS | On `http://127.0.0.1:8000/`, “shop có loại gấu bông khác không” showed a single in-stock teddy card titled “Gấu bông Teddy Hug”; no chocolate or unrelated gift cards appeared. |
| Live OpenRouter Free check | PASS (after one timeout) | The first live request timed out and used the safe fallback. A second regular recommendation request reached OpenRouter Free with HTTP 200, valid structured output, and no fallback. |
| GiftAdvisor focused tests | PASS | 49 tests, 288 assertions. |
| Full regression | PASS | 136 tests, 1,036 assertions. |
| Database | NONE | No schema, seed, catalogue, order, or other database changes; browser verification only read BloomGift products. |

Latest runtime fields from the successful online browser request:

```text
AI_PROVIDER_ACTIVE=openrouter
AI_MODEL=openrouter/free
AI_REQUEST_SENT=true
AI_HTTP_STATUS=200
AI_RESPONSE_RECEIVED=true
STRUCTURED_OUTPUT_VALID=true
AI_FALLBACK_USED=false
DATABASE_CHANGE=NONE
FOCUSED_TEST=PASS (49 tests, 288 assertions)
FULL_TEST=PASS (136 tests, 1036 assertions)
```

## OpenRouter runtime verification

```text
AI_PROVIDER=openrouter
AI_MODEL=openrouter/free
AI_HTTP_STATUS=200
AI_RESPONSE_RECEIVED=YES
STRUCTURED_OUTPUT_VALID=YES
AI_FALLBACK_USED=NO
DATABASE_CHANGE=NONE
FULL_TEST=PASS (136 tests, 1036 assertions)
```

## AI Advisor quality repair — 2026-10-10

```text
AI_PROVIDER=OPENROUTER
AI_MODEL=openrouter/free
NEGATIVE_CONSTRAINT_PERSISTENCE=PASS
MULTI_NEGATION_PARSE=PASS
EXCLUSION_POST_VALIDATION=PASS
SUPPRESS_SUGGESTIONS=PASS
SUPPRESSION_CLEAR=PASS
ORDINAL_SELECTION_VALID=PASS
ORDINAL_SELECTION_INVALID=PASS
SELECTED_ITEM_MEMORY=PASS
SELECTED_CONCEPT_EDIT=PASS
TITLE_DUPLICATION=PASS
WHY_RECOMMENDED_QUALITY=PASS
PRONOUN_CONSISTENCY=PASS
AI_HTTP_STATUS=429 (latest live request; OpenRouter rate limit)
AI_RESPONSE_RECEIVED=YES (HTTP 429 error response; no usable model content)
AI_FALLBACK_USED=YES (latest live request)
DATABASE_CHANGE=NONE
BROWSER_VERIFIED=YES
TARGETED_CONTROLLER_TEST=PASS (1 test, 5 assertions)
FULL_TEST=PASS (145 tests, 1114 assertions)
```

The latest browser query used the OpenRouter provider/model and received HTTP 429, so BloomGift returned its safe Vietnamese fallback. Earlier live verification recorded below reached HTTP 200 with valid structured output and no fallback. Browser verification after the controller validation fix selected “Memory Keepsake Album”, changed its palette to “trắng / kem”, kept the album and card contents, and displayed the updated palette in the concept details. The live flow no longer returns an HTML validation page to the JSON/SSE chat client.

A controller validation allowlist now accepts the concept detail fields produced by the Advisor, including `design_description` and `greenery`. The card detail renderer displays `color_palette` so gift and combo edits remain visible. A feature test reproduces selecting a custom gift card and editing it through the controller. No database schema or data was changed; regression tests use an in-memory SQLite database.

