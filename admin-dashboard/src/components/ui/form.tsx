import * as React from "react"
import * as LabelPrimitive from "@radix-ui/react-label"
import { Slot } from "@radix-ui/react-slot"
import {
  Controller,
  FormProvider,
  useFormContext,
  type ControllerProps,
  type FieldPath,
  type FieldValues,
} from "react-hook-form"

import { cn } from "@/lib/utils"
import { Label } from "@/components/ui/label"

const Form = FormProvider

type FormFieldContextValue<
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
> = { name: TName }

const FormFieldContext = React.createContext<FormFieldContextValue>({} as FormFieldContextValue)

const FormField = <
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
>({ ...props }: ControllerProps<TFieldValues, TName>) => (
  <FormFieldContext.Provider value={{ name: props.name }}>
    <Controller {...props} />
  </FormFieldContext.Provider>
)

const useFormField = () => {
  const fieldContext = React.useContext(FormFieldContext)
  const itemContext = React.useContext(FormItemContext)
  const { getFieldState } = useFormContext()
  const fieldState = getFieldState(fieldContext.name)

  if (!fieldContext) throw new Error("useFormField must be used within FormField")
  return { id: itemContext.id, name: fieldContext.name, ...fieldState }
}

const FormItemContext = React.createContext<{ id: string }>({} as { id: string })

function FormItem({ className, ...props }: React.ComponentProps<"div">) {
  const id = React.useId()
  return (
    <FormItemContext.Provider value={{ id }}>
      <div data-slot="form-item" className={cn("grid gap-2", className)} {...props} />
    </FormItemContext.Provider>
  )
}

function FormLabel({ className, ...props }: React.ComponentProps<typeof LabelPrimitive.Root>) {
  const { error, id } = useFormField()
  return <Label data-slot="form-label" data-error={!!error} className={cn(dataLabelClasses, className)} htmlFor={`${id}-form-item`} {...props} />
}

function FormControl({ ...props }: React.ComponentProps<typeof Slot>) {
  const { error, id } = useFormField()
  return <Slot data-slot="form-control" id={`${id}-form-item`} aria-describedby={!error ? `${id}-form-item-description` : `${id}-form-item-message`} aria-invalid={!!error} {...props} />
}

function FormDescription({ className, ...props }: React.ComponentProps<"p">) {
  const { id } = useFormField()
  return <p data-slot="form-description" id={`${id}-form-item-description`} className={cn("text-muted-foreground text-sm", className)} {...props} />
}

function FormMessage({ className, ...props }: React.ComponentProps<"p">) {
  const { error, id } = useFormField()
  const body = error ? String(error.message ?? "") : props.children
  if (!body) return null
  return <p data-slot="form-message" id={`${id}-form-item-message`} className={cn("text-destructive text-sm", className)} {...props}>{body}</p>
}

const dataLabelClasses = "flex items-center gap-2 text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50"

export { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage, useFormField }
