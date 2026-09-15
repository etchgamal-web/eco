import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

export function Login() {
  return <div className="flex min-h-screen items-center justify-center bg-muted/20"><Card className="w-full max-w-sm"><CardHeader><CardTitle>Sign in</CardTitle></CardHeader><CardContent className="space-y-4"><Input type="email" placeholder="Email" /><Input type="password" placeholder="Password" /><Button className="w-full">Login</Button></CardContent></Card></div>
}
