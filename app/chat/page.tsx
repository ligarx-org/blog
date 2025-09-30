"use client"

import type React from "react"

import { useState, useEffect, useRef } from "react"
import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { ScrollArea } from "@/components/ui/scroll-area"
import { ArrowLeft, Send, Search } from "lucide-react"
import { apiEndpoints, apiCall } from "@/lib/api-config"

interface ChatUser {
  id: number
  username: string
  avatar: string
  last_message?: string
  last_message_time?: string
  unread_count?: number
}

interface Message {
  id: number
  sender_id: number
  receiver_id: number
  message: string
  created_at: string
  sender_username: string
  sender_avatar: string
}

interface BlogUser {
  id: number
  username: string
  email: string
  avatar: string
}

export default function ChatPage() {
  const router = useRouter()
  const [currentUser, setCurrentUser] = useState<BlogUser | null>(null)
  const [users, setUsers] = useState<ChatUser[]>([])
  const [selectedUser, setSelectedUser] = useState<ChatUser | null>(null)
  const [messages, setMessages] = useState<Message[]>([])
  const [newMessage, setNewMessage] = useState("")
  const [searchQuery, setSearchQuery] = useState("")
  const messagesEndRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    checkUserSession()
  }, [])

  useEffect(() => {
    if (currentUser) {
      loadUsers()
      const interval = setInterval(loadUsers, 5000) // Refresh every 5 seconds
      return () => clearInterval(interval)
    }
  }, [currentUser, searchQuery])

  useEffect(() => {
    if (selectedUser && currentUser) {
      loadMessages()
      const interval = setInterval(loadMessages, 2000) // Refresh messages every 2 seconds
      return () => clearInterval(interval)
    }
  }, [selectedUser, currentUser])

  useEffect(() => {
    scrollToBottom()
  }, [messages])

  const checkUserSession = () => {
    const token = localStorage.getItem("blog_token")
    const user = localStorage.getItem("blog_user")
    if (token && user) {
      setCurrentUser(JSON.parse(user))
    } else {
      router.push("/")
    }
  }

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" })
  }

  const loadUsers = async () => {
    try {
      const token = localStorage.getItem("blog_token")
      console.log("Loading chat users with token:", token ? "present" : "missing")
      const data = await apiCall(`${apiEndpoints.chatUsers}&search=${searchQuery}&token=${token}`)
      if (data.success) {
        setUsers(data.users)
        console.log("Chat users loaded:", data.users.length)
      } else {
        console.error("Failed to load chat users:", data)
      }
    } catch (error) {
      console.error("Error loading users:", error)
    }
  }

  const loadMessages = async () => {
    if (!selectedUser) return

    try {
      const token = localStorage.getItem("blog_token")
      console.log("Loading messages between users:", { currentUser: currentUser?.id, selectedUser: selectedUser.id })
      const data = await apiCall(`${apiEndpoints.chatMessages}&user_id=${selectedUser.id}&token=${token}`)
      if (data.success) {
        setMessages(data.messages)
        console.log("Messages loaded:", data.messages.length)
      } else {
        console.error("Failed to load messages:", data)
      }
    } catch (error) {
      console.error("Error loading messages:", error)
    }
  }

  const sendMessage = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!newMessage.trim() || !selectedUser) return

    try {
      console.log("Sending message:", { to: selectedUser.id, message: newMessage })
      const data = await apiCall(apiEndpoints.sendMessage, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("blog_token")}`,
        },
        body: JSON.stringify({
          receiver_id: selectedUser.id,
          message: newMessage,
        }),
      })

      if (data.success) {
        setNewMessage("")
        loadMessages()
        loadUsers() // Refresh user list to update last message
        console.log("Message sent successfully")
      } else {
        console.error("Failed to send message:", data)
      }
    } catch (error) {
      console.error("Send message error:", error)
    }
  }

  if (!currentUser) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-foreground mb-4">Tizimga kiring</h1>
          <Button onClick={() => router.push("/")}>Bosh sahifaga qaytish</Button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="sticky top-0 z-50 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <Button variant="ghost" onClick={() => router.push("/")}>
              <ArrowLeft className="h-4 w-4 mr-2" />
              Orqaga
            </Button>
            <h1 className="text-xl font-bold text-primary">Chat</h1>
            <div className="w-20"></div>
          </div>
        </div>
      </header>

      <div className="container mx-auto px-4 py-6">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-200px)]">
          {/* Users List */}
          <Card className="lg:col-span-1">
            <CardHeader>
              <CardTitle className="text-lg">Foydalanuvchilar</CardTitle>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                <Input
                  placeholder="Qidirish..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
            </CardHeader>
            <CardContent className="p-0">
              <ScrollArea className="h-[500px]">
                <div className="space-y-1 p-4">
                  {users.map((user) => (
                    <div
                      key={user.id}
                      onClick={() => setSelectedUser(user)}
                      className={`flex items-center space-x-3 p-3 rounded-lg cursor-pointer transition-colors ${
                        selectedUser?.id === user.id ? "bg-primary/10 border border-primary/20" : "hover:bg-muted"
                      }`}
                    >
                      <Avatar className="h-10 w-10">
                        <AvatarImage src={`${apiEndpoints.uploads}/${user.avatar}`} />
                        <AvatarFallback>{user.username[0].toUpperCase()}</AvatarFallback>
                      </Avatar>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                          <p className="font-medium text-foreground truncate">{user.username}</p>
                          {user.unread_count && user.unread_count > 0 && (
                            <span className="bg-primary text-primary-foreground text-xs rounded-full px-2 py-1 min-w-[20px] text-center">
                              {user.unread_count}
                            </span>
                          )}
                        </div>
                        {user.last_message && (
                          <p className="text-sm text-muted-foreground truncate">{user.last_message}</p>
                        )}
                      </div>
                    </div>
                  ))}

                  {users.length === 0 && (
                    <div className="text-center py-8">
                      <p className="text-muted-foreground">Foydalanuvchilar topilmadi</p>
                    </div>
                  )}
                </div>
              </ScrollArea>
            </CardContent>
          </Card>

          {/* Chat Area */}
          <Card className="lg:col-span-2">
            {selectedUser ? (
              <>
                <CardHeader className="border-b">
                  <div className="flex items-center space-x-3">
                    <Avatar className="h-10 w-10">
                      <AvatarImage src={`${apiEndpoints.uploads}/${selectedUser.avatar}`} />
                      <AvatarFallback>{selectedUser.username[0].toUpperCase()}</AvatarFallback>
                    </Avatar>
                    <div>
                      <h3 className="font-medium text-foreground">{selectedUser.username}</h3>
                      <p className="text-sm text-muted-foreground">Onlayn</p>
                    </div>
                  </div>
                </CardHeader>

                <CardContent className="p-0 flex flex-col h-[500px]">
                  {/* Messages */}
                  <ScrollArea className="flex-1 p-4">
                    <div className="space-y-4">
                      {messages.map((message) => (
                        <div
                          key={message.id}
                          className={`flex ${message.sender_id === currentUser.id ? "justify-end" : "justify-start"}`}
                        >
                          <div
                            className={`max-w-[70%] rounded-lg px-4 py-2 ${
                              message.sender_id === currentUser.id
                                ? "bg-primary text-primary-foreground"
                                : "bg-muted text-foreground"
                            }`}
                          >
                            <p className="text-sm">{message.message}</p>
                            <p
                              className={`text-xs mt-1 ${
                                message.sender_id === currentUser.id
                                  ? "text-primary-foreground/70"
                                  : "text-muted-foreground"
                              }`}
                            >
                              {new Date(message.created_at).toLocaleTimeString("uz-UZ", {
                                hour: "2-digit",
                                minute: "2-digit",
                              })}
                            </p>
                          </div>
                        </div>
                      ))}
                      <div ref={messagesEndRef} />
                    </div>
                  </ScrollArea>

                  {/* Message Input */}
                  <div className="border-t p-4">
                    <form onSubmit={sendMessage} className="flex space-x-2">
                      <Input
                        placeholder="Xabar yozing..."
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                        className="flex-1"
                      />
                      <Button type="submit" disabled={!newMessage.trim()}>
                        <Send className="h-4 w-4" />
                      </Button>
                    </form>
                  </div>
                </CardContent>
              </>
            ) : (
              <CardContent className="flex items-center justify-center h-[500px]">
                <div className="text-center">
                  <p className="text-muted-foreground mb-4">Suhbatni boshlash uchun foydalanuvchini tanlang</p>
                </div>
              </CardContent>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}
