import { forwardRef, useEffect, useState } from 'react';
import NumberInput from '@/components/NumberInput';
import { Input } from '@/components/ui/input';
import { isMeasured, parseQty, qtyToText } from '@/lib/units';

// Quantity in steps of the unit. Pieces use the plain number input; kg/liter
// take a decimal ("1,52") and hand back grams/ml (1520).
const QuantityInput = forwardRef(function QuantityInput({ unit, value, onChange, ...props }, ref) {
    const [text, setText] = useState(() => qtyToText(value, unit));

    // Follow changes from outside (quick buttons), but leave "1," alone while typing.
    useEffect(() => {
        if (parseQty(text, unit) !== value) setText(qtyToText(value, unit));
    }, [value, unit]);

    if (!isMeasured(unit)) return <NumberInput ref={ref} value={value} onChange={onChange} {...props} />;

    return (
        <Input
            ref={ref}
            type="text"
            inputMode="decimal"
            autoComplete="off"
            value={text}
            onChange={(e) => {
                const next = e.target.value.replace(/[^\d.,]/g, '');
                setText(next);
                onChange(parseQty(next, unit));
            }}
            {...props}
        />
    );
});

export default QuantityInput;
