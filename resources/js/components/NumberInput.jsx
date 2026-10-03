import { forwardRef } from 'react';
import { Input } from '@/components/ui/input';
import { formatNumber, parseNumber } from '@/lib/format';

// Number input with thousand separators and a numeric keypad on phones.
const NumberInput = forwardRef(function NumberInput({ value, onChange, ...props }, ref) {
    return (
        <Input
            ref={ref}
            type="text"
            inputMode="numeric"
            autoComplete="off"
            value={value === '' || value == null ? '' : formatNumber(value)}
            onChange={(e) => onChange(parseNumber(e.target.value))}
            {...props}
        />
    );
});

export default NumberInput;
