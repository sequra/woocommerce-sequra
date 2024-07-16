# Pending tests

## Settings workflow

## Widget workflow

## Payment Workflow
- [ ] **Unit**: Check seQura product availability based on address or country of the shopper.
- [X] **E2E**: The available seQura products appear in the checkout
- [X] **E2E**: Make a successful payment using any shopper name
- [ ] **E2E**: Make a successful payment using any shopper name (using services)
- [X] **E2E**: Make a 🍊 payment with "Review test approve" names
- [ ] **E2E**: Make a 🍊 payment with "Review test cancel" names
- [ ] **E2E**: Make a payment attempt forcing a failure in the update order in timon step, by changing the order payload amounts so it differs with the approved one.

### Unit: Payload is correctly generated
- [ ] Payload is correct when configured to show taxes in the front-end
- [ ] Payload is correct when configured not to show taxes in the front-end
- [ ] Order total matches the sum of the items in the cart
- [ ] If services is enabled:
    - [ ] Items are of type services.
    - [ ] ...
- [ ] If services is no enabled:
    - [ ] 