---
kind: code
---

## Why

`SigningProviderInterface` declares `cancelSigning(string $externalId): bool`. Both
providers implement it. **Nothing calls it.**

```
$ grep -rn '\->cancelSigning(' lib/ src/
(no output)
```

So a signing request, once sent, cannot be withdrawn through DocuDesk. The capability
was built and never connected — gate-57 surfaced it on 2026-08-16 as one of exactly
three genuine orphaned write capabilities in the app.

That alone would be an ordinary gap. What makes it urgent is the state the two
implementations are in.

### The two providers do not do the same thing

`NativeSigningProvider::cancelSigning()` genuinely cancels:

```php
$session = $this->loadSessionByExternalId(externalId: $externalId);
$session['status'] = 'cancelled';
$this->persistSession(session: $session);
return true;
```

`ValidSignProvider::cancelSigning()` is **a stub**:

```php
public function cancelSigning(string $externalId): bool {
    return true;
}
```

It makes no call to ValidSign. It returns `true` unconditionally.

**Wiring a cancel button to the interface as it stands would be worse than leaving
the capability disconnected.** A user cancels a ValidSign request, is told it
succeeded, and the request stays live at the provider — signatories can still open
and sign a document the user believes withdrawn, and the resulting signature is
legally valid. The user has no way to discover this from DocuDesk.

That is the "reports success, changes nothing" failure with a legal consequence
attached.

## What Changes

- **`ValidSignProvider::cancelSigning()` actually calls ValidSign**, or throws. The
  one thing it must never do again is return `true` without contacting the provider.
- **`SigningCancellationService`** owning the cancellation: resolve the request,
  authorise the actor, call the provider, record the outcome, update the request's
  state.
- **A controller route and a UI action**, so the capability is reachable.
- **An authorisation rule** — the open question this change exists to settle. See
  below.
- **`cancelSigning()` returns void or throws**, rather than `bool`. A boolean return
  invites `if ($ok)` at one call site and a bare call at another, and the stub above
  is what a `bool` contract encourages.

## The decision this change needs from a human

**Who may cancel a signing request?** The candidates are not equivalent:

| Candidate | Argument for | Argument against |
|---|---|---|
| The request's creator only | Simplest, clearly authorised | A creator on leave blocks everyone |
| Creator + anyone with write on the document | Matches Nextcloud's sharing model | Write access to a file is not obviously authority to withdraw a legal process |
| Creator + app admin | Gives an escape hatch | Admin is not a role in the signing domain |

This is not a detail to be picked by whoever implements it. Cancelling a signing
request is a legally meaningful act, and the wrong answer is either an
authorisation hole or an operational dead end. **The proposal deliberately does not
choose.**

## Capabilities

### New Capabilities
- `signing-cancellation`: how a signing request is withdrawn, who may do it, and what
  the system must never claim.

## Impact

- **Code**: `lib/Service/Signing/ValidSignProvider.php` (real implementation),
  `lib/Service/Signing/NativeSigningProvider.php` (return shape),
  `lib/Service/Signing/SigningProviderInterface.php` (return shape), new
  `SigningCancellationService`, a controller method, a route, a UI action.
- **Behaviour**: a capability that currently does not exist for users becomes
  available. Nothing that works today changes.
- **Not in scope**: `BatchStateService::deleteBatch`, the third orphan gate-57 found.
  It is unrelated to signing and needs its own decision about what deleting a batch
  should do to the documents in it.

## Related

- Found by hydra gate-57. Twelve of that gate's fifteen docudesk findings were false
  positives — routed controller methods it could not see a caller for — fixed in
  [ConductionNL/.github#475](https://github.com/ConductionNL/.github/pull/475). These
  three are the real remainder.
