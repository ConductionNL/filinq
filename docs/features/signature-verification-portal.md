# Signature verification portal (in progress)

## Overview

Anyone holding a document Filinq signed can check what its signatures
guarantee without a Nextcloud account. When a signing request completes,
Filinq stores the outcome of the signatures under a random token (128 bits,
32 hexadecimal characters) and puts that token on the signing request as
`verificationToken`. The public endpoint answers by token.

## What is built

- **Record**: the `signatureVerification` schema in the `filinq` register
  (register 8.52.0). It holds the file name, the SHA-256 of the signed bytes,
  and per signature its status, level, method, the signer as written in the
  signature, and two separate guarantees. It never holds the document, the
  signing key or a path. Only the server writes it; through the OpenRegister
  API only signing admins read it.
- **Minting**: signing completion reads the stored signed version back as the
  initiator and mints the record. A record that cannot be minted is logged;
  it does not undo the signature.
- **Public endpoint**: `GET /apps/filinq/api/verify/{token}` (public,
  rate-limited, brute-force throttled on every unknown token).

## What the answer says

Two guarantees are reported separately for every signature:

- `integrityVerified`: the document bytes are unchanged since this server
  signed them.
- `identityBound`: the signer fields are covered by the same check, so they
  cannot be rewritten without the check failing. Current (v2) signatures
  cover them; a legacy or external signature does not, and its signer is
  shown only as asserted.

Each signature is `verified`, `invalid` (changed after signing) or
`unverifiable` (an external signature, or one this instance cannot check). An
unverified signature is never reported with either guarantee, whatever the
record says.

A token that names nothing, a malformed token, a revoked record and a store
that cannot be read all answer `200` with `status: unknown` in the same shape,
so the endpoint does not reveal whether a token exists.

A seal named on the record is reported as `unverifiable` with
`seal-check-unavailable`: seal verification is not part of this app yet.

## Not built yet

- The public page itself (`verify/{token}`) and its registration.
- The QR code on signed PDFs that links to the page.
- Minting from the seal path and for LibreSign completions.
