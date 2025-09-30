"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { useRouter, useSearchParams } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Textarea } from "@/components/ui/textarea"
import { Badge } from "@/components/ui/badge"
import { Heart, MessageCircle, ArrowLeft, Send, User, Share2, Bookmark, Eye } from "lucide-react"
import { apiEndpoints, apiCall } from "@/lib/api-config"

interface Post {
  id: number
  title: string
  content: string
  image: string
  hashtags: string
  username: string
  avatar: string
  like_count: number
  comment_count: number
  view_count?: number
  created_at: string
}

interface Comment {
  id: number
  content: string
  username: string
  avatar: string
  created_at: string
}

interface BlogUser {
  id: number
  username: string
  email: string
  avatar: string
}

export default function PostPage() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const [post, setPost] = useState<Post | null>(null)
  const [comments, setComments] = useState<Comment[]>([])
  const [newComment, setNewComment] = useState("")
  const [currentUser, setCurrentUser] = useState<BlogUser | null>(null)
  const [loading, setLoading] = useState(true)
  const [isLiked, setIsLiked] = useState(false)
  const [isBookmarked, setIsBookmarked] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Get post ID from URL params or cookies
  const getPostId = () => {
    const urlId = searchParams.get('id')
    const cookieId = typeof window !== 'undefined' ? localStorage.getItem('current_post_id') : null
    return urlId || cookieId || '1'
  }

  useEffect(() => {
    checkUserSession()
    const postId = getPostId()
    if (postId) {
      // Save to localStorage for persistence
      localStorage.setItem('current_post_id', postId)
      loadPost(postId)
      loadComments(postId)
    }
  }, [searchParams])

  const checkUserSession = () => {
    const token = localStorage.getItem("blog_token")
    const user = localStorage.getItem("blog_user")
    if (token && user) {
      setCurrentUser(JSON.parse(user))
    }
  }

  const loadPost = async (postId: string) => {
    try {
      setLoading(true)
      setError(null)
      
      console.log("Loading post:", postId)

      const apiUrl = `${apiEndpoints.posts.replace("?action=posts", "")}?action=post&id=${postId}`
      console.log("Post API URL:", apiUrl)
      
      const data = await apiCall(apiUrl)

      if (data.success) {
        setPost(data.post)
        incrementViewCount(postId)
        console.log("Post loaded successfully:", data.post.title)
      } else {
        console.error("Post loading failed:", data)
        setError(data.message || "Post topilmadi")
      }
    } catch (error) {
      console.error("Error loading post:", error)
      setError("Xatolik yuz berdi")
    } finally {
      setLoading(false)
    }
  }

  const incrementViewCount = async (postId: string) => {
    try {
      console.log("Incrementing view count for post:", postId)
      await apiCall(`${apiEndpoints.posts.replace("?action=posts", "")}?action=view&post_id=${postId}`, {
        method: "POST",
      })
    } catch (error) {
      console.error("Error incrementing view count:", error)
    }
  }

  const loadComments = async (postId: string) => {
    try {
      console.log("Loading comments for post:", postId)
      const data = await apiCall(`${apiEndpoints.comments}&post_id=${postId}`)
      if (data.success) {
        setComments(data.comments)
        console.log("Comments loaded:", data.comments.length)
      } else {
        console.error("Comments loading failed:", data)
      }
    } catch (error) {
      console.error("Error loading comments:", error)
    }
  }

  const handleLike = async () => {
    if (!currentUser) {
      alert("Tizimga kiring")
      return
    }

    const postId = getPostId()
    try {
      const data = await apiCall(apiEndpoints.like, {
        method: "POST",
        body: JSON.stringify({
          post_id: postId,
          token: localStorage.getItem("blog_token"),
        }),
      })

      if (data.success && post) {
        setPost({ ...post, like_count: data.like_count })
        setIsLiked(!isLiked)
      }
    } catch (error) {
      console.error("Like error:", error)
    }
  }

  const handleShare = async () => {
    const currentUrl = `${window.location.origin}/post?id=${getPostId()}`
    if (navigator.share) {
      try {
        await navigator.share({
          title: post?.title,
          text: post?.title,
          url: currentUrl,
        })
      } catch (error) {
        console.error("Error sharing:", error)
      }
    } else {
      navigator.clipboard.writeText(currentUrl)
      alert("Havola nusxalandi!")
    }
  }

  const handleComment = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!currentUser || !newComment.trim()) return

    const postId = getPostId()
    try {
      const data = await apiCall(apiEndpoints.comments, {
        method: "POST",
        body: JSON.stringify({
          post_id: postId,
          content: newComment,
          token: localStorage.getItem("blog_token"),
        }),
      })

      if (data.success) {
        setNewComment("")
        loadComments(postId)
        if (post) {
          setPost({ ...post, comment_count: post.comment_count + 1 })
        }
      }
    } catch (error) {
      console.error("Comment error:", error)
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-background to-muted/20 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-muted-foreground">Post yuklanmoqda...</p>
        </div>
      </div>
    )
  }

  if (error || !post) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-background to-muted/20 flex items-center justify-center">
        <div className="text-center max-w-md mx-auto p-6">
          <div className="w-16 h-16 bg-destructive/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <ArrowLeft className="h-8 w-8 text-destructive" />
          </div>
          <h1 className="text-2xl font-bold text-foreground mb-2">Post topilmadi</h1>
          <p className="text-muted-foreground mb-6">{error || "Bunday post mavjud emas"}</p>
          <Button onClick={() => router.push("/")} className="w-full">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Bosh sahifaga qaytish
          </Button>
        </div>
      </div>
    )
  }

  const hashtags = post.hashtags ? post.hashtags.split(",").filter((tag) => tag.trim()) : []

  return (
    <div className="min-h-screen bg-gradient-to-br from-background to-muted/20">
      {/* Enhanced Header */}
      <header className="sticky top-0 z-50 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b border-border/40">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <Button variant="ghost" onClick={() => router.push("/")} className="hover:bg-primary/10">
              <ArrowLeft className="h-4 w-4 mr-2" />
              Orqaga
            </Button>
            <h1 className="text-xl font-bold bg-gradient-to-r from-primary to-primary/70 bg-clip-text text-transparent">
              CodeBlog
            </h1>
            <Button variant="ghost" onClick={handleShare} className="hover:bg-primary/10">
              <Share2 className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </header>

      <main className="container mx-auto px-4 py-8 max-w-4xl">
        {/* Enhanced Post Content */}
        <Card className="mb-8 overflow-hidden shadow-xl border-0 bg-gradient-to-br from-card to-card/50">
          {post.image && (
            <div className="aspect-video relative overflow-hidden">
              <img
                src={post.image.startsWith("http") ? post.image : `${apiEndpoints.uploads}/${post.image}`}
                alt={post.title}
                className="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            </div>
          )}

          <CardContent className="p-8">
            <h1 className="text-4xl font-bold text-foreground mb-6 leading-tight">{post.title}</h1>

            {/* Author Info */}
            <div className="flex items-center justify-between mb-6">
              <div className="flex items-center space-x-4">
                <Avatar className="h-12 w-12 border-2 border-primary/20">
                  <AvatarImage src={post.avatar ? `${apiEndpoints.uploads}/${post.avatar}` : undefined} />
                  <AvatarFallback className="bg-gradient-to-br from-primary to-primary/70 text-primary-foreground">
                    {post.username[0].toUpperCase()}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <p
                    className="font-semibold text-foreground hover:text-primary cursor-pointer"
                    onClick={() => router.push(`/profile?username=${post.username}`)}
                  >
                    {post.username}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {new Date(post.created_at).toLocaleDateString("uz-UZ", {
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                  </p>
                </div>
              </div>

              {/* View Count */}
              {post.view_count && (
                <div className="flex items-center text-muted-foreground text-sm">
                  <Eye className="h-4 w-4 mr-1" />
                  {post.view_count} ko'rildi
                </div>
              )}
            </div>

            {/* Action Buttons */}
            <div className="flex items-center justify-between mb-8 p-4 bg-muted/30 rounded-xl">
              <div className="flex items-center space-x-6">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={handleLike}
                  className={`flex items-center space-x-2 hover:bg-red-500/10 ${isLiked ? "text-red-500" : ""}`}
                >
                  <Heart className={`h-5 w-5 ${isLiked ? "fill-current" : ""}`} />
                  <span className="font-medium">{post.like_count}</span>
                </Button>

                <div className="flex items-center space-x-2 text-muted-foreground">
                  <MessageCircle className="h-5 w-5" />
                  <span className="font-medium">{post.comment_count} izoh</span>
                </div>
              </div>

              <div className="flex items-center space-x-2">
                <Button variant="ghost" size="sm" onClick={() => setIsBookmarked(!isBookmarked)}>
                  <Bookmark className={`h-5 w-5 ${isBookmarked ? "fill-current text-primary" : ""}`} />
                </Button>
                <Button variant="ghost" size="sm" onClick={handleShare}>
                  <Share2 className="h-5 w-5" />
                </Button>
              </div>
            </div>

            {/* Hashtags */}
            {hashtags.length > 0 && (
              <div className="flex flex-wrap gap-2 mb-6">
                {hashtags.map((tag, index) => (
                  <Badge key={index} variant="secondary" className="bg-primary/10 text-primary hover:bg-primary/20">
                    #{tag.trim()}
                  </Badge>
                ))}
              </div>
            )}

            {/* Post Content */}
            <div
              className="prose prose-lg max-w-none dark:prose-invert prose-headings:text-foreground prose-p:text-foreground prose-strong:text-foreground prose-a:text-primary hover:prose-a:text-primary/80"
              dangerouslySetInnerHTML={{ __html: post.content }}
            />
          </CardContent>
        </Card>

        {/* Enhanced Comments Section */}
        <Card className="shadow-xl border-0 bg-gradient-to-br from-card to-card/50">
          <CardContent className="p-8">
            <h2 className="text-2xl font-bold text-foreground mb-6 flex items-center">
              <MessageCircle className="h-6 w-6 mr-2" />
              Izohlar ({comments.length})
            </h2>

            {/* Add Comment Form */}
            {currentUser ? (
              <form onSubmit={handleComment} className="mb-8">
                <div className="flex space-x-4">
                  <Avatar className="h-10 w-10 border-2 border-primary/20">
                    <AvatarImage
                      src={currentUser.avatar ? `${apiEndpoints.uploads}/${currentUser.avatar}` : undefined}
                    />
                    <AvatarFallback className="bg-gradient-to-br from-primary to-primary/70 text-primary-foreground">
                      {currentUser.username[0].toUpperCase()}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1">
                    <Textarea
                      placeholder="Izoh yozing..."
                      value={newComment}
                      onChange={(e) => setNewComment(e.target.value)}
                      className="mb-3 border-primary/20 focus:border-primary/40"
                      rows={3}
                    />
                    <Button
                      type="submit"
                      disabled={!newComment.trim()}
                      className="bg-gradient-to-r from-primary to-primary/80 hover:from-primary/90 hover:to-primary/70"
                    >
                      <Send className="h-4 w-4 mr-2" />
                      Yuborish
                    </Button>
                  </div>
                </div>
              </form>
            ) : (
              <div className="mb-8 p-6 bg-gradient-to-r from-muted/50 to-muted/30 rounded-xl text-center border border-border/50">
                <p className="text-muted-foreground mb-3">Izoh qoldirish uchun tizimga kiring</p>
                <Button
                  variant="outline"
                  onClick={() => router.push("/")}
                  className="border-primary/30 hover:bg-primary/10"
                >
                  <User className="h-4 w-4 mr-2" />
                  Tizimga kirish
                </Button>
              </div>
            )}

            {/* Comments List */}
            <div className="space-y-6">
              {comments.length === 0 ? (
                <div className="text-center py-12">
                  <MessageCircle className="h-12 w-12 text-muted-foreground/50 mx-auto mb-4" />
                  <p className="text-muted-foreground text-lg">
                    Hozircha izohlar yo'q. Birinchi bo'lib izoh qoldiring!
                  </p>
                </div>
              ) : (
                comments.map((comment) => (
                  <div
                    key={comment.id}
                    className="flex space-x-4 p-4 bg-muted/20 rounded-xl hover:bg-muted/30 transition-colors"
                  >
                    <Avatar className="h-10 w-10 border-2 border-primary/20">
                      <AvatarImage src={comment.avatar ? `${apiEndpoints.uploads}/${comment.avatar}` : undefined} />
                      <AvatarFallback className="bg-gradient-to-br from-primary to-primary/70 text-primary-foreground">
                        {comment.username[0].toUpperCase()}
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex-1">
                      <div className="flex items-center space-x-2 mb-2">
                        <span
                          className="font-semibold text-foreground hover:text-primary cursor-pointer"
                          onClick={() => router.push(`/profile?username=${comment.username}`)}
                        >
                          {comment.username}
                        </span>
                        <span className="text-sm text-muted-foreground">
                          {new Date(comment.created_at).toLocaleDateString("uz-UZ", {
                            month: "short",
                            day: "numeric",
                            hour: "2-digit",
                            minute: "2-digit",
                          })}
                        </span>
                      </div>
                      <p className="text-foreground leading-relaxed">{comment.content}</p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>
      </main>
    </div>
  )
}