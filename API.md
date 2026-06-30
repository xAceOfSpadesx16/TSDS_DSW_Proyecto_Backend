# DSW-Backend — API Reference

Documentación de los endpoints HTTP expuestos por **Thrive Randomizer — DSW-Backend** (Laravel 12 + JWT + arquitectura modular).

> **Estado del documento:** generado contra el código en `main` después de las fases 1–4 del `implementation_plan.md`. Cualquier cambio en rutas, validaciones o shape de respuesta debe reflejarse acá.

---

## 1. Información general

| Campo | Valor |
|---|---|
| Base URL (dev) | `http://127.0.0.1:8000` |
| Prefijo de la API | `/api` |
| Versión | `1.0.0` |
| Content-Type | `application/json` (request y response) |
| Charset | `UTF-8` |
| Auth scheme | `Authorization: Bearer <jwt>` |

El backend es 100% stateless. **No hay sesiones ni cookies**. Toda la autenticación viaja por JWT en el header `Authorization`.

---

## 2. Autenticación

1. El cliente se registra (`POST /api/auth/register`) o inicia sesión (`POST /api/auth/login`).
2. La respuesta contiene un `token` JWT firmado (algoritmo `HS256` por defecto de Tymon).
3. En cada request subsiguiente a rutas protegidas, el cliente envía:

   ```http
   Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
   ```

4. El token tiene un TTL configurable (`JWT_TTL` en minutos, default 60). Para renovarlo, el cliente vuelve a hacer `POST /api/auth/login`.
5. El logout invalida el token server-side (`auth:api` lo bloquea en requests posteriores con `401`).

### Claims relevantes del JWT

| Claim | Significado |
|---|---|
| `sub` | ID del usuario (PK en `users`) |
| `iat` | Timestamp de emisión |
| `exp` | Timestamp de expiración |
| `iss` | URL absoluta desde donde se emitió (útil para auditar) |

---

## 3. Manejo de errores

Todas las respuestas de error siguen la misma estructura:

```json
{
  "message": "Descripción legible del error."
}
```

Las respuestas de **validación** (HTTP `422`) además incluyen los errores por campo:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Códigos HTTP usados

| Código | Significado | Cuándo ocurre |
|---|---|---|
| `200` | OK | Request exitosa con body |
| `201` | Created | Recurso persistido |
| `401` | Unauthorized | Token ausente, expirado o inválido |
| `404` | Not Found | Ruta inexistente o recurso no encontrado |
| `422` | Unprocessable Entity | Validación de FormRequest fallida |
| `500` | Internal Server Error | Excepción no controlada (modo no-debug) |

---

## 4. Endpoints

### 4.1 Health

#### `GET /api/health`

Chequeo de disponibilidad. Útil para readiness probes y para que el frontend verifique la API antes de pegar.

- **Auth requerida:** no
- **Query params:** ninguno
- **Body:** no

**Response 200**

```json
{
  "status": "ok",
  "service": "DSW-Backend",
  "version": "1.0.0",
  "timestamp": "2026-06-29T21:47:45+00:00"
}
```

---

### 4.2 Auth — `App\Modules\Auth`

#### `POST /api/auth/register`

Crea un nuevo usuario y emite su primer JWT. No requiere autenticación previa.

- **Auth requerida:** no
- **Query params:** ninguno

**Request body**

```json
{
  "name": "Ada Lovelace",              // required · string · max:255
  "email": "ada@example.com",          // required · string · email · max:255 · unique:users,email
  "password": "super-secret"           // required · string · min:8
}
```

**Response 201**

```json
{
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "email_verified_at": null,
    "created_at": "2026-06-29T21:44:00.000000Z",
    "updated_at": "2026-06-29T21:44:00.000000Z"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Errores comunes**

- `422` — email duplicado o validaciones fallidas:

  ```json
  {
    "message": "The email has already been taken.",
    "errors": {
      "email": ["The email has already been taken."]
    }
  }
  ```

---

#### `POST /api/auth/login`

Autentica credenciales y emite un JWT nuevo. Idempotente en el sentido de que **siempre crea un token nuevo** (el anterior sigue válido hasta su `exp`, salvo logout explícito).

- **Auth requerida:** no
- **Query params:** ninguno

**Request body**

```json
{
  "email": "ada@example.com",          // required · string · email
  "password": "super-secret"           // required · string
}
```

**Response 200**

```json
{
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Errores comunes**

- `401` — credenciales inválidas:

  ```json
  {
    "message": "Credenciales inválidas."
  }
  ```

- `422` — payload mal formado (falta email o password, email no es formato válido).

---

#### `POST /api/auth/logout`

Invalida el JWT actual server-side (lo agrega a la denylist interna de Tymon).

- **Auth requerida:** sí (`Authorization: Bearer <jwt>`)
- **Query params:** ninguno
- **Body:** no

**Response 200**

```json
{
  "message": "Sesión cerrada correctamente."
}
```

**Errores comunes**

- `401` — token ausente o inválido:

  ```json
  {
    "message": "No autenticado."
  }
  ```

---

#### `GET /api/auth/me`

Retorna los datos del usuario autenticado a partir del token Bearer.

- **Auth requerida:** sí
- **Query params:** ninguno
- **Body:** no

**Response 200**

```json
{
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "email_verified_at": null,
    "created_at": "2026-06-29T21:44:00.000000Z",
    "updated_at": "2026-06-29T21:44:00.000000Z"
  }
}
```

**Errores comunes**

- `401` — token ausente, expirado o inválido.

---

### 4.3 History — `App\Modules\History`

> **Regla de oro:** `user_id` **nunca** se acepta del cliente. El backend siempre lo toma del JWT. Aunque el cliente envíe `"user_id": 9999`, será descartado por `CreateRequest::prepareForValidation()`.

#### `POST /api/history`

Persiste un registro de sorteo enviado por el cliente. El sorteo ya fue calculado en el frontend; el backend solo guarda el resultado.

- **Auth requerida:** sí
- **Query params:** ninguno

**Request body**

```json
{
  "module_name": "DiceRoller",          // required · string · max:255
  "action": "roll",                     // required · string · max:255
  "payload": {                          // required · object (JSON estructurado, NO string)
    "dice": "2d6+3"
  },
  "result": {                           // required · object (JSON estructurado, NO string)
    "total": 11
  }
}
```

> Los campos `payload` y `result` son **arrays arbitrarios** — el backend no interpreta su forma interna, solo exige que sean JSON estructurado (no strings ni números sueltos).

**Response 201**

```json
{
  "data": {
    "id": "019f1556-dca5-7113-8d23-3448781606cd",
    "user_id": 1,
    "module_name": "DiceRoller",
    "action": "roll",
    "payload": { "dice": "2d6+3" },
    "result": { "total": 11 },
    "created_at": "2026-06-29T21:44:01+00:00",
    "updated_at": "2026-06-29T21:44:01+00:00"
  }
}
```

**Errores comunes**

- `401` — sin token:

  ```json
  {
    "message": "No autenticado."
  }
  ```

- `422` — `payload` o `result` no son arrays:

  ```json
  {
    "message": "The payload field must be an array. (and 1 more error)",
    "errors": {
      "payload": ["The payload field must be an array."],
      "result": ["The result field must be an array."]
    }
  }
  ```

---

#### `GET /api/histories`

Lista paginada del historial del usuario autenticado, ordenado del más reciente al más antiguo. **Aísla por dueño:** nunca se ven registros de otros usuarios.

- **Auth requerida:** sí
- **Body:** no

**Query params**

| Nombre | Tipo | Default | Rango | Descripción |
|---|---|---|---|---|
| `page` | int | `1` | `≥ 1` | Número de página a recuperar. Manejado automáticamente por el paginador de Laravel. |
| `per_page` | int | `20` | `1..100` | Elementos por página. Valores fuera de rango se **clampan** (ej. `per_page=999` → `100`; `per_page=0` o negativo → `1`; no numérico → `20`). |

**Ejemplo de request**

```http
GET /api/histories?page=2&per_page=10
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

**Response 200**

```json
{
  "data": [
    {
      "id": "019f1556-dca5-7113-8d23-3448781606cd",
      "user_id": 1,
      "module_name": "DiceRoller",
      "action": "roll",
      "payload": { "dice": "2d6+3" },
      "result": { "total": 11 },
      "created_at": "2026-06-29T21:44:01+00:00",
      "updated_at": "2026-06-29T21:44:01+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

**Errores comunes**

- `401` — sin token.

---

### 4.4 Legal — `App\Modules\Legal`

#### `GET /api/legal/disclaimer`

Retorna el disclaimer principal del producto. Texto estático por ahora; centralizado en `LegalService` para facilitar evolución a persistencia DB o i18n.

- **Auth requerida:** no
- **Query params:** ninguno
- **Body:** no

**Response 200**

```json
{
  "title": "Disclaimer - Thrive Randomizer",
  "version": "1.0.0",
  "sections": [
    {
      "heading": "Naturaleza del servicio",
      "body": "Thrive Randomizer es una herramienta recreativa..."
    },
    {
      "heading": "Cálculo en el cliente",
      "body": "La generación de resultados aleatorios se realiza íntegramente en el navegador..."
    },
    {
      "heading": "Sin garantía de equidad criptográfica",
      "body": "Los algoritmos utilizados (Fisher-Yates, ruleta geométrica, sorteos ponderados) son adecuados para uso recreativo..."
    },
    {
      "heading": "Datos personales",
      "body": "El registro solicita únicamente nombre, email y contraseña..."
    },
    {
      "heading": "Limitación de responsabilidad",
      "body": "El uso de la herramienta es responsabilidad exclusiva del usuario..."
    }
  ]
}
```

---

## 5. Convenciones transversales

### Fechas

Todos los timestamps vienen en **ISO 8601** con offset UTC (`+00:00` o `Z`).

### Identificadores

| Recurso | Tipo de ID |
|---|---|
| `users` | `bigint` autoincremental |
| `history_records` | `uuid v4` (string) |

### Aislamiento por usuario

Toda la data persistente (excepto `users` mismos) está atada al dueño autenticado. El backend **nunca** confía en IDs o flags enviados por el cliente: la fuente de verdad siempre es el JWT.

### Versionado

Esta es la versión `1.0.0` del contrato. Cambios incompatibles (romper shapes, cambiar códigos) implicarán un bump de versión y, posiblemente, un prefijo `/api/v2/...`. Cambios aditivos (campos nuevos opcionales) son compatibles hacia atrás.

---

## 6. Smoke tests rápidos (cURL)

```bash
# 1) Registro
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Ada","email":"ada@example.com","password":"super-secret"}'

# 2) Login
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ada@example.com","password":"super-secret"}'

# 3) Me (reemplazar <token> por el JWT recibido)
curl http://127.0.0.1:8000/api/auth/me \
  -H "Authorization: Bearer <token>"

# 4) Crear historial
curl -X POST http://127.0.0.1:8000/api/history \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"module_name":"DiceRoller","action":"roll","payload":{"dice":"2d6+3"},"result":{"total":11}}'

# 5) Listar historial paginado
curl "http://127.0.0.1:8000/api/histories?page=1&per_page=10" \
  -H "Authorization: Bearer <token>"

# 6) Disclaimer
curl http://127.0.0.1:8000/api/legal/disclaimer
```

---

## 7. Próximos pasos sugeridos

1. **OpenAPI / Swagger**: generar un `openapi.yaml` desde las rutas (Laravel Spectrum o scribe) para tooling tipo Postman/Insomnia.
2. **Rate limiting**: hoy no hay; recomendable añadir `throttle:60,1` a las rutas de auth.
3. **Refresh tokens**: cuando los JWT expiren, actualmente hay que volver a hacer `login`. Se puede implementar refresh token (rotación + denylist).
4. **Versionado explícito**: mover endpoints bajo `/api/v1/...` cuando crezca la superficie.
