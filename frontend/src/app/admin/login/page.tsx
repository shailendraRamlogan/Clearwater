"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { login, isAdmin } from "@/lib/admin-auth";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function AdminLoginPage() {
  const router = useRouter();
  const [token, setToken] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    if (isAdmin()) {
      router.replace("/admin/dashboard");
    }
  }, [router]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!token.trim()) {
      setError("Token is required");
      return;
    }
    login(token.trim());
    router.push("/admin/dashboard");
  };

  return (
    <div className="section-container py-20">
      <div className="max-w-md mx-auto">
        <Card>
          <CardHeader>
            <CardTitle className="text-xl">Admin Login</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <Label htmlFor="token">Admin Token</Label>
                <Input
                  id="token"
                  type="password"
                  placeholder="Enter your admin token"
                  value={token}
                  onChange={(e) => {
                    setToken(e.target.value);
                    setError("");
                  }}
                />
                {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
              </div>
              <Button type="submit" variant="cta" className="w-full">
                Login
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
