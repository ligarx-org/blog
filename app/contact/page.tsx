"use client"

import type React from "react"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Mail, Phone, MapPin, Send } from "lucide-react"

export default function ContactPage() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    message: "",
    captcha: "",
  })
  const [captchaUrl, setCaptchaUrl] = useState(`/api/captcha.php?${Date.now()}`)
  const [loading, setLoading] = useState(false)

  const refreshCaptcha = () => {
    setCaptchaUrl(`/api/captcha.php?${Date.now()}`)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)

    try {
      const response = await fetch("/api/api.php?action=contact", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      })
      const data = await response.json()

      if (data.success) {
        alert("Sizning habaringiz muvaffaqiyatli yuborildi!")
        setFormData({ name: "", email: "", message: "", captcha: "" })
        refreshCaptcha()
      } else {
        alert(data.message)
        refreshCaptcha()
      }
    } catch (error) {
      console.error("Contact form error:", error)
      alert("Xatolik yuz berdi. Qaytadan urinib ko'ring.")
    }
    setLoading(false)
  }

  return (
    <div className="min-h-screen bg-background">
      <div className="container mx-auto px-4 py-8">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-8">
            <h1 className="text-4xl font-bold mb-4 text-balance">Biz bilan bog'laning</h1>
            <p className="text-xl text-muted-foreground text-pretty">
              Savollaringiz bormi? Biz sizga yordam berishga tayyormiz!
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-8">
            {/* Contact Form */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center">
                  <Send className="h-5 w-5 mr-2" />
                  Xabar yuborish
                </CardTitle>
                <CardDescription>Formani to'ldiring va biz tez orada javob beramiz</CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleSubmit} className="space-y-4">
                  <div>
                    <label className="text-sm font-medium mb-2 block">Ismingiz</label>
                    <Input
                      placeholder="To'liq ismingizni kiriting"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      required
                    />
                  </div>

                  <div>
                    <label className="text-sm font-medium mb-2 block">Email</label>
                    <Input
                      type="email"
                      placeholder="email@example.com"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      required
                    />
                  </div>

                  <div>
                    <label className="text-sm font-medium mb-2 block">Xabar</label>
                    <Textarea
                      placeholder="Xabaringizni yozing..."
                      rows={5}
                      value={formData.message}
                      onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                      required
                    />
                  </div>

                  <div>
                    <label className="text-sm font-medium mb-2 block">Xavfsizlik kodi</label>
                    <div className="flex items-center space-x-2 mb-2">
                      <img src={captchaUrl || "/placeholder.svg"} alt="Captcha" className="border rounded" />
                      <Button type="button" variant="outline" size="sm" onClick={refreshCaptcha}>
                        Yangilash
                      </Button>
                    </div>
                    <Input
                      placeholder="Yuqoridagi kodni kiriting"
                      value={formData.captcha}
                      onChange={(e) => setFormData({ ...formData, captcha: e.target.value })}
                      required
                    />
                  </div>

                  <Button type="submit" className="w-full" disabled={loading}>
                    {loading ? "Yuborilmoqda..." : "Xabar yuborish"}
                  </Button>
                </form>
              </CardContent>
            </Card>

            {/* Contact Information */}
            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Bog'lanish ma'lumotlari</CardTitle>
                  <CardDescription>Bizga quyidagi usullar orqali murojaat qilishingiz mumkin</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center space-x-3">
                    <Mail className="h-5 w-5 text-primary" />
                    <div>
                      <p className="font-medium">Email</p>
                      <p className="text-muted-foreground">info@codeblog.uz</p>
                    </div>
                  </div>

                  <div className="flex items-center space-x-3">
                    <Phone className="h-5 w-5 text-primary" />
                    <div>
                      <p className="font-medium">Telefon</p>
                      <p className="text-muted-foreground">+998 90 123 45 67</p>
                    </div>
                  </div>

                  <div className="flex items-center space-x-3">
                    <MapPin className="h-5 w-5 text-primary" />
                    <div>
                      <p className="font-medium">Manzil</p>
                      <p className="text-muted-foreground">Toshkent, O'zbekiston</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Ish vaqti</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    <div className="flex justify-between">
                      <span>Dushanba - Juma</span>
                      <span className="text-muted-foreground">9:00 - 18:00</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Shanba</span>
                      <span className="text-muted-foreground">10:00 - 16:00</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Yakshanba</span>
                      <span className="text-muted-foreground">Dam olish</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
