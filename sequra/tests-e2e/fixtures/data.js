const defaultShopperData = {
    address1: "Carrer d'Alí Bei, 7",
    email: 'test@sequra.es',
    city: 'Barcelona',
    state: 'Barcelona',
    postcode: '08010',
    country: 'Spain',
    phone: '666666666',
    dateOfBirth: '01/01/2000',
    dni: '23232323T',
    creditCard: {
        number: '4716773077339777',
        exp: '12/30',
        cvc: '123',
    },
    otp: ['6', '6', '6', '6', '6']
}

export const shopper = {
    approve: {
        firstName: 'Review Test Approve',
        lastName: 'Review Test Approve',
        ...defaultShopperData

    },
    cancel: {
        firstName: 'Review Test Cancel',
        lastName: 'Review Test Cancel',
        ...defaultShopperData

    },
    nonSpecial: {
        firstName: 'Fulano',
        lastName: 'De Tal',
        ...defaultShopperData
    }
}

export const sqProduct = {
    fp1: {
        es: {
            name: 'Paga con tarjeta',
        }
    },
    i1: {
        es: {
            name: 'Paga Después',
        }
    },
    sp1: {
        es: {
            name: 'Divide tu pago en 3',
        }
    },
    pp3: {
        es: {
            name: 'Paga Fraccionado',
        }
    },
    pp3Decombined: {
        es: {
            name: "€/mes (DECOMBINED)",
        }
    }
}

export const merchant = {
    dummy: {
        username: 'dummy',
        password: process.env.DUMMY_PASSWORD,
        assetsKey: process.env.DUMMY_ASSETS_KEY,
        ref: {
            ES: 'dummy',
            FR: 'dummy_fr',
            IT: 'dummy_it',
            PT: 'dummy_pt',
            CO: 'dummy_co',
            PE: 'dummy_pe',
        },
        paymentMethods: {
            ES: [
                'Paga con tarjeta',
                'Paga Después',
                'Divide tu pago en 3',
                'Paga Fraccionado',
                'Divide en 3 0,00 €/mes (DECOMBINED)'
            ],
            FR: [
                'Payez en plusieurs fois'
            ],
            PT: [
                'Divida seu pagamento em 3'
            ],
            IT: [
                'Dividi il tuo pagamento in 3'
            ],
            CO: [],
            PE: []
        }
    }
}

export const countries = {
    default: {
        ES: 'Spain',
        FR: 'France',
        IT: 'Italy',
        PT: 'Portugal',
        CO: 'Colombia',
        PE: 'Peru',
    }
}