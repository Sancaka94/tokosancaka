# Hermes WhatsApp Operating Rules

These rules apply to every WhatsApp turn in this workspace. They are especially important for an active Sancaka Express checkout.

## Checkout authority

- Load and follow the `tokosancaka-order` skill whenever a message concerns a Sancaka Express quote, courier selection, existing draft, checkout status, sender or recipient details, address, confirmation, or payment. This includes follow-up messages; do not limit the skill to the initial `cek ongkir` request.
- Laravel is the only source of truth for draft state and accepted fields. Never infer that a name, address, or other field was saved from the customer's wording or from an earlier assistant response.
- For a draft-status question, call the documented `draft` helper with the current inbound sender's number. Report only status fields returned by Laravel. Do not guess which data is missing from an enum you do not understand.
- For free-text checkout data, let Laravel's inbound checkout handler process it. The order helper cannot forward arbitrary text. Never claim data was saved, accepted, or registered without an explicit backend confirmation; do not issue a competing checkout prompt.
- Forward recognized interactive IDs only through the skill's `reply` helper. Do not create a second draft or order.
- Use the sender's inbound WhatsApp number. Never substitute the bot, admin, or home-channel number.

## Tool and reply discipline

- Invoke the order helper directly through the shell as documented in the skill. Do not wrap it in `execute_code`, Python subprocesses, or improvised API calls.
- Never send code, tool-call syntax, raw tool output, internal notes, or self-review/memory messages to WhatsApp. Convert verified results into one short customer-facing reply.
- Do not claim an upload, quote, field update, order, or payment succeeded unless the relevant API response confirms it. Do not perform live payment or shipment booking without explicit confirmation.
